<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\ItTicketCreator;
use App\Models\UserModel;
use App\Services\MsOAuthTokenService;
use App\Services\ImapXoauth2Client;
use App\Services\EmailMimeHelper;
use App\Models\InboxProcessedEmailModel;

class InboxPullMS extends BaseCommand
{
    protected $group = 'Inbox';
    protected $name = 'inbox:pull-ms';
    protected $description = 'Pull unread emails from Microsoft IMAP via XOAUTH2 and create IT tickets ONLY if sender is an intranet user. Attach .eml + attachments separately.';

    public function run(array $params)
    {
        $lock = new \App\Services\CommandLock('ms_inbox_pull');
        try {
            if (!$lock->acquire(900)) {
                CLI::write('SKIP: ms inbox:pull már fut (lock aktív).');
                return;
            }
        } catch (\Throwable $e) {
            CLI::error('LOCK ERROR: ' . $e->getMessage());
            return;
        }

        $host = getenv('MS_INBOX_IMAP_HOST') ?: 'outlook.office365.com';
        $port = (int) (getenv('MS_INBOX_IMAP_PORT') ?: 993);
        $mailboxUser = getenv('MS_INBOX_IMAP_USER');
        $mailbox = getenv('MS_INBOX_IMAP_MAILBOX') ?: 'INBOX';

        $processedFolder = getenv('MS_INBOX_IMAP_PROCESSED_FOLDER') ?: 'Processed';
        $rejectedFolder = getenv('MS_INBOX_REJECTED_FOLDER') ?: 'Rejected';

        $defaultAreaId = (int) (getenv('MS_INBOX_DEFAULT_AREA_ID') ?: 1);
        $defaultCategoryId = (int) (getenv('MS_INBOX_DEFAULT_CATEGORY_ID') ?: 1);

        if (!$mailboxUser) {
            CLI::error('Missing env: MS_INBOX_IMAP_USER');
            $lock->release();
            return;
        }

        $tokenService = new MsOAuthTokenService();
        $imap = new ImapXoauth2Client();
        $mime = new EmailMimeHelper();

        $creator = new ItTicketCreator();
        $userModel = new UserModel();
        $dedupeModel = new InboxProcessedEmailModel();

        $created = 0;
        $commented = 0;
        $rejected = 0;
        $errors = 0;
        $skipped = 0;

        try {
            $accessToken = $tokenService->getAccessToken();

            $imap->connect($host, $port);
            $imap->authenticateXoauth2($mailboxUser, $accessToken);
            $imap->selectMailbox($mailbox);

            $uids = $imap->uidSearchUnseen();
            if (!$uids) {
                CLI::write('Nincs új levél.');
                $imap->close();
                return;
            }

            rsort($uids);

            foreach ($uids as $uid) {
                $lock->heartbeat();

                $fromEmail = null;
                $messageId = null;

                try {
                    $rawEml = $imap->uidFetchRfc822($uid);

                    $headers = $mime->parseBasicHeaders($rawEml);
                    $fromEmail = $headers['from_email'] ?? null;
                    $subjectText = trim($headers['subject'] ?? '') ?: '(nincs tárgy)';
                    $messageId = $headers['message_id'] ?? null;

                    $ticketAreaId = $defaultAreaId;
                    $ticketName = $subjectText;

                    if (preg_match('/^\s*HRK:\s*/iu', $ticketName)) {
                        $ticketAreaId = 71;
                        $ticketName = preg_replace('/^\s*HRK:\s*/iu', '', $ticketName);
                        $ticketName = trim($ticketName);
                    }

                    if ($ticketName === '') {
                        $ticketName = '(nincs tárgy)';
                    }

                    if (!$messageId) {
                        $messageId = 'noid-' . sha1(substr($rawEml, 0, 4096));
                    }

                    $messageId = trim($messageId);
                    $messageId = preg_replace('/\s+/', '', $messageId);

                    $decision = $dedupeModel->acquireOrDecide($messageId, $fromEmail, 600, true);

                    if (!$decision['acquired']) {
                        $reason = $decision['reason'] ?? 'unknown';
                        CLI::write("SKIP: {$reason} message-id {$messageId} uid #{$uid}");

                        if (in_array($reason, ['success', 'rejected'], true)) {
                            try {
                                $imap->uidSetSeen($uid);
                            } catch (\Throwable $ignored) {
                            }

                            try {
                                $imap->uidMove($uid, $processedFolder);
                            } catch (\Throwable $ignored) {
                            }
                        }

                        $skipped++;
                        continue;
                    }

                    if (!$fromEmail) {
                        CLI::error("HIBA: msg uid #{$uid}: nincs feladó email (From header)");
                        $dedupeModel->markFailed($messageId, 'Missing From header', null);
                        $imap->uidSetSeen($uid);
                        $errors++;
                        continue;
                    }

                    $senderUser = $this->findUserByEmail($userModel, $fromEmail);
                    if (!$senderUser || !isset($senderUser->id)) {
                        CLI::write("REJECT: {$fromEmail} (nem intranet user) uid #{$uid}");
                        $dedupeModel->markRejected($messageId, $fromEmail);
                        $imap->uidMove($uid, $rejectedFolder);
                        $rejected++;
                        continue;
                    }

                    $emlTempPath = $mime->saveEmlTemp($rawEml);

                    $parsed = $mime->extractBodyAndAttachmentsFromEml($emlTempPath);
                    $bodyTextRaw = (string) ($parsed['bodyText'] ?? '');
                    $attachmentPaths = (array) ($parsed['attachments'] ?? []);

                    $descriptionText = trim($bodyTextRaw);

                    $replyTicketId = $this->findReplyTicketId($dedupeModel, $headers);

                    if ($replyTicketId) {
                        $commentText = $this->prepareEmailReplyCommentText($subjectText, $descriptionText);

                        $creator->addComment(
                            (int) $replyTicketId,
                            $this->textToSafeHtml($commentText),
                            (int) $senderUser->id
                        );

                        $dedupeModel->markSuccess($messageId, $replyTicketId, $fromEmail);

                        $imap->uidMove($uid, $processedFolder);

                        $commented++;
                        CLI::write("OK: {$fromEmail} uid #{$uid} -> comment ticket #{$replyTicketId}");

                        continue;
                    }

                    $descriptionText = $this->prepareNewTicketDescriptionText($subjectText, $descriptionText);

                    if ($descriptionText === '') {
                        $descriptionText = '(Üres leírás vagy nem olvasható tartalom – lásd a csatolt .eml fájlt.)';
                    }

                    $description = $this->textToSafeHtml($descriptionText);
                    $participantIds = $this->findParticipantIdsFromCc(
                        $userModel,
                        $headers['cc'] ?? '',
                        (int) $senderUser->id
                    );

                    $ticketId = $creator->create([
                        'sender_id' => (int) $senderUser->id,
                        'uploader_id' => (int) $senderUser->id,
                        'validator' => (int) $senderUser->id,
                        'area' => $ticketAreaId,
                        'category' => $defaultCategoryId,
                        'email' => $fromEmail,
                        'phone' => $senderUser->phone ?? null,
                        'name' => $ticketName,
                        'description' => $description,
                        'deadline' => date('Y-m-d', strtotime('+1 weeks')),
                        'participants' => $participantIds,
                    ], array_merge([$emlTempPath], $attachmentPaths));

                    $this->renameEmlToTicketNumber($ticketId);

                    $dedupeModel->markSuccess($messageId, $ticketId, $fromEmail);

                    $imap->uidMove($uid, $processedFolder);

                    $created++;
                    CLI::write("OK: {$fromEmail} uid #{$uid} -> ticket #{$ticketId} (attachments: " . count($attachmentPaths) . ")");

                } catch (\Throwable $e) {
                    $errors++;

                    if ($messageId) {
                        try {
                            $dedupeModel->markFailed($messageId, $e->getMessage(), $fromEmail);
                        } catch (\Throwable $ignored) {
                        }
                    }

                    try {
                        $imap->uidSetSeen($uid);
                    } catch (\Throwable $ignored) {
                    }

                    CLI::error("HIBA: msg uid #{$uid}: " . $e->getMessage());
                }
            }

            $imap->close();

        } catch (\Throwable $e) {
            CLI::error('FATAL: ' . $e->getMessage());

            try {
                $imap->close();
            } catch (\Throwable $ignored) {
            }

            return;

        } finally {
            $lock->release();
        }

        CLI::write("Kész. created={$created}, commented={$commented}, rejected={$rejected}, skipped={$skipped}, errors={$errors}");
    }

    private function findUserByEmail(UserModel $userModel, string $email)
    {
        $email = strtolower(trim($email));
        return $userModel->where('email', $email)->first();
    }

    private function stripSignatureIfNotForwarded(string $subject, string $body): string
    {
        if ($this->isForwardedMail($subject, $body)) {
            return trim($body);
        }
        return $this->stripSignature($body);
    }

    private function isForwardedMail(string $subject, string $body): bool
    {
        $subjectL = mb_strtolower(trim($subject));
        $bodyL = mb_strtolower($body);

        if (preg_match('/^(fw|fwd)\s*:/i', $subjectL) || str_starts_with($subjectL, 'továbbítás:')) {
            return true;
        }

        $strong = [
            '---------- forwarded message ----------',
            '-----original message-----',
            '-------- eredeti üzenet --------',
        ];

        foreach ($strong as $m) {
            if (strpos($bodyL, $m) !== false)
                return true;
        }

        $hasFrom = preg_match('/\nfrom:\s?.+/i', $body);
        $hasSent = preg_match('/\nsent:\s?.+/i', $body);
        $hasTo = preg_match('/\nto:\s?.+/i', $body);
        $hasSubject = preg_match('/\nsubject:\s?.+/i', $body);

        return ($hasFrom && $hasSent && $hasTo && $hasSubject);
    }

    private function stripSignature(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $patterns = [
            "/\n--\n.*/s",
            "/\n---\n.*/s",

            "/\nÜdvözlettel\s*\/\s*Best\s+Re?agards.*$/isu",
            "/\nÜdvözlettel.*$/isu",
            "/\nTisztelettel.*$/isu",
            "/\nBest\s+regards.*$/isu",
            "/\nBest\s+reagards.*$/isu",
            "/\nKind\s+regards.*$/isu",

            "/\nKöszönettel.*$/isu",
            "/\nKöszi.*$/isu",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $text = preg_replace($pattern, '', $text);
                break;
            }
        }

        return trim($text);
    }

    private function textToSafeHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $paragraphs = preg_split("/\n{2,}/", trim($text));
        $paragraphs = array_map(function ($p) {
            $p = nl2br(trim($p), false);
            return '<p>' . $p . '</p>';
        }, $paragraphs);

        return implode("\n", $paragraphs);
    }

    private function renameEmlToTicketNumber(int $ticketId): void
    {
        $ticketModel = new \App\Modules\ItTickets\Models\ItTicketsModel();
        $attachmentModel = new \App\Modules\ItTickets\Models\ItTicketAttachmentsModel();

        $ticket = $ticketModel->find($ticketId);
        if (!$ticket || empty($ticket->task_number)) {
            return;
        }

        $ticketNumber = $ticket->task_number;
        $basePath = FCPATH . 'uploads/it_tickets/' . $ticketId . '/';

        if (!is_dir($basePath)) {
            return;
        }

        $files = glob($basePath . '__ORIGINAL_MESSAGE__*.eml');
        if (!$files) {
            return;
        }

        usort($files, static function ($a, $b) {
            return (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0);
        });

        $oldPath = $files[0];

        $newName = $ticketNumber . '.eml';
        $newPath = $basePath . $newName;

        if ($oldPath === $newPath) {
            return;
        }

        if (!@rename($oldPath, $newPath)) {
            return;
        }

        $attachmentModel
            ->where('ticket_id', $ticketId)
            ->like('filename', '__ORIGINAL_MESSAGE__')
            ->like('filename', '.eml')
            ->set(['filename' => $newName])
            ->update();
    }

    private function extractReplyMessageIds(array $headers): array
    {
        $messageIds = [];

        foreach (['in_reply_to', 'references'] as $key) {
            if (empty($headers[$key])) {
                continue;
            }

            preg_match_all('/<[^>]+>/', (string) $headers[$key], $matches);

            foreach ($matches[0] ?? [] as $id) {
                $id = trim($id);
                $id = preg_replace('/\s+/', '', $id);

                if ($id !== '') {
                    $messageIds[] = $id;
                }
            }
        }

        return array_values(array_unique($messageIds));
    }

    private function findReplyTicketId(InboxProcessedEmailModel $dedupeModel, array $headers): ?int
    {
        $replyMessageIds = $this->extractReplyMessageIds($headers);

        if (!$replyMessageIds) {
            return null;
        }

        return $dedupeModel->findSuccessfulTicketByMessageIds($replyMessageIds);
    }

    private function prepareEmailReplyCommentText(string $subject, string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return 'Email válasz érkezett, de a levél törzse nem volt olvasható.';
        }

        $body = $this->cleanEmailTextForStorage($body);
        $body = $this->extractLatestEmailReply($body);
        $body = $this->stripSignature($body);
        $body = $this->cleanEmailTextForStorage($body);

        if (trim($body) === '') {
            return 'Email válasz érkezett, de a levél törzse nem volt olvasható.';
        }

        return trim($body);
    }

    private function prepareNewTicketDescriptionText(string $subject, string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return '';
        }

        $body = $this->cleanEmailTextForStorage($body);
        $body = $this->stripSignatureIfNotForwarded($subject, $body);
        $body = $this->cleanEmailTextForStorage($body);

        return trim($body);
    }

    private function cleanEmailTextForStorage(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $body = $this->removeCssNoise($body);
        $body = $this->removeMimeAttachmentNoise($body);
        $body = $this->removeDataUriBase64Noise($body);

        $body = preg_replace("/[ \t]+\n/", "\n", $body);
        $body = preg_replace("/\n{3,}/", "\n\n", $body);

        return trim($body);
    }
    private function findParticipantIdsFromCc(UserModel $userModel, ?string $ccHeader, int $senderUserId): array
    {
        $emails = $this->extractEmailsFromHeader((string) $ccHeader);

        if (!$emails) {
            return [];
        }

        $emails = array_values(array_unique(array_map(static function ($email) {
            return strtolower(trim($email));
        }, $emails)));

        $users = $userModel
            ->select('id, email')
            ->whereIn('email', $emails)
            ->findAll();

        if (!$users) {
            return [];
        }

        $ids = [];

        foreach ($users as $user) {
            if (!isset($user->id)) {
                continue;
            }

            $id = (int) $user->id;

            if ($id > 0 && $id !== $senderUserId) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function extractEmailsFromHeader(string $header): array
    {
        if ($header === '') {
            return [];
        }

        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $header, $matches);

        return $matches[0] ?? [];
    }

    private function extractLatestEmailReply(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = trim($body);

        if ($body === '') {
            return '';
        }

        $markers = [
            // Outlook angol
            '/\nFrom:\s.+/i',
            '/\nSent:\s.+/i',
            '/\nTo:\s.+/i',
            '/\nSubject:\s.+/i',

            // Outlook magyar
            '/\nFeladó:\s.+/iu',
            '/\nKüldve:\s.+/iu',
            '/\nCímzett:\s.+/iu',
            '/\nTárgy:\s.+/iu',

            // Forward / original message blokkok
            '/\n[-]{2,}\s*Original Message\s*[-]{2,}/iu',
            '/\n[-]{2,}\s*Forwarded message\s*[-]{2,}/iu',
            '/\n[-]{2,}\s*Eredeti üzenet\s*[-]{2,}/iu',
            '/\n[-]{2,}\s*Továbbított üzenet\s*[-]{2,}/iu',

            // Gmail / általános reply jelölők
            '/\nOn .+ wrote:\s*$/imu',
            '/\n.* ezt írta .*:\s*$/imu',
            '/\n.* írta:\s*$/imu',
        ];

        $cutAt = null;

        foreach ($markers as $pattern) {
            if (preg_match($pattern, $body, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];

                if ($pos > 0 && ($cutAt === null || $pos < $cutAt)) {
                    $cutAt = $pos;
                }
            }
        }

        if ($cutAt !== null) {
            $body = substr($body, 0, $cutAt);
        }
        $body = $this->removeQuotedLines($body);
        $body = $this->cleanEmailTextForStorage($body);

        return trim($body);
    }


    private function removeQuotedLines(string $body): string
    {
        $lines = preg_split("/\n/", $body);
        $clean = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $clean[] = '';
                continue;
            }

            if (str_starts_with($trimmed, '>')) {
                continue;
            }

            $clean[] = $line;
        }

        $body = implode("\n", $clean);

        $body = preg_replace("/\n{3,}/", "\n\n", $body);

        return trim($body);
    }


    private function removeCssNoise(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $patterns = [
            '/^\s*P\s*\{\s*margin-top\s*:\s*0\s*;\s*margin-bottom\s*:\s*0\s*;\s*\}\s*$/imu',

            '/^\s*(BODY|HTML|DIV|SPAN|P|LI|UL|OL|TABLE|TD|TH)\s*\{[^}]*\}\s*$/imu',

            '/<style\b[^>]*>[\s\S]*?<\/style>/iu',
        ];

        foreach ($patterns as $pattern) {
            $body = preg_replace($pattern, '', $body);
        }

        $body = preg_replace("/\n{3,}/", "\n\n", $body);

        return trim($body);
    }

    private function removeDataUriBase64Noise(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $body = preg_replace(
            '/\s(?:src|href)=["\']data:[^"\']+;base64,[^"\']+["\']/iu',
            '',
            $body
        );

        $body = preg_replace(
            '/data:[a-z0-9\/.\-+]+(?:;[a-z0-9=\-+._]+)*;base64,[A-Za-z0-9+\/=\r\n]+/iu',
            '',
            $body
        );

        return trim($body);
    }

    private function removeMimeAttachmentNoise(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $patterns = [
            '/\n--[A-Za-z0-9_=\-\.\/:\+]{8,}[\s\S]*$/u',
            '/^--[A-Za-z0-9_=\-\.\/:\+]{8,}[\s\S]*$/u',

            '/\nContent-Type:\s*(?:application|image|audio|video|multipart|message)\/[^\n]+[\s\S]*$/iu',
            '/\nContent-Type:\s*text\/calendar[^\n]*[\s\S]*$/iu',
            '/\nContent-Disposition:\s*(?:attachment|inline)[^\n]*[\s\S]*$/iu',
            '/\nContent-Transfer-Encoding:\s*base64[\s\S]*$/iu',
            '/\nContent-ID:\s*<?[^>\n]+>?[\s\S]*$/iu',
            '/\nContent-Description:\s*[^\n]+[\s\S]*$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body)) {
                $body = preg_replace($pattern, '', $body);
            }
        }

        return trim($body);
    }
}
