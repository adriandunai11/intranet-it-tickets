<?php

namespace App\Modules\ItTickets\Services;

use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Modules\ItTickets\Models\ItTicketAttachmentsModel;
use App\Modules\ItTickets\Models\ItTicketNotesModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

class ItTicketCreator
{

    public function create(array $payload, array $files = []): int
    {
        $model = new ItTicketsModel();

        foreach (['area', 'category', 'name'] as $key) {
            if (!array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
                throw new \InvalidArgumentException("Hiányzó kötelező mező: {$key}");
            }
        }

        $taskNumber = $this->unique_random();

        $description = $payload['description'] ?? '';

        $result = $model->create([
            'sender_id' => $payload['sender_id'] ?? null,
            'task_number' => $taskNumber,
            'area' => $payload['area'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'category' => $payload['category'],
            'deadline' => $payload['deadline'] ?? date('Y-m-d', strtotime('+1 weeks')),
            'name' => $payload['name'],
            'description' => $description,
            'validator' => $payload['validator'] ?? null,
            'created_at' => $payload['created_at'] ?? date('Y-m-d H:i:s'),
            'participants' => json_encode($payload['participants'] ?? []),
        ]);

        $id = null;
        if (is_numeric($result)) {
            $id = (int) $result;
        } elseif (is_object($result) && isset($result->id)) {
            $id = (int) $result->id;
        }

        if (!$id) {
            throw new \RuntimeException('Ticket létrehozás sikertelen: nincs ID visszatérés');
        }

        $cleanDescription = (new \App\Services\ItTicketInlineImageService())
            ->normalizeStoredHtml(
                (string) ($payload['description'] ?? ''),
                $id,
                isset($payload['uploader_id'])
                ? (int) $payload['uploader_id']
                : (isset($payload['sender_id']) ? (int) $payload['sender_id'] : null)
            );

        if ($cleanDescription !== (string) ($payload['description'] ?? '')) {
            $model->update($id, [
                'description' => $cleanDescription,
            ]);
        }

        $this->storeAttachments($id, $files, $payload['uploader_id'] ?? ($payload['sender_id'] ?? null));

        return $id;
    }

    private function unique_random(): string
    {
        $model = new ItTicketsModel();
        $year = date('Y');
        $prefix = 'IT' . date('y');

        try {
            $model->db->transStart();

            $row = $model->db
                ->query(
                    "SELECT MAX(CAST(SUBSTRING(task_number, 5) AS UNSIGNED)) AS max_num
                 FROM {$model->table}
                 WHERE YEAR(created_at) = ?
                 FOR UPDATE",
                    [$year]
                )
                ->getRow();

            $nextNum = ($row->max_num ?? 0) + 1;

            $model->db->transComplete();

            return $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }

    private function storeAttachments(int $ticketId, array $files, ?int $uploaderId): void
    {
        if (empty($files))
            return;

        $path = 'uploads/it_tickets/' . $ticketId;
        $targetDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        foreach ($files as $file) {

            if (is_string($file) && is_file($file)) {
                $filename = basename($file);

                @copy($file, $targetDir . DIRECTORY_SEPARATOR . $filename);

                (new ItTicketAttachmentsModel)->create([
                    'ticket_id' => $ticketId,
                    'path' => $path,
                    'filename' => $filename,
                    'created' => date('Y-m-d H:i:s'),
                    'uploader' => $uploaderId,
                ]);

                continue;
            }

            if (is_object($file) && method_exists($file, 'isValid') && $file->isValid() && !$file->hasMoved()) {
                $file->move($targetDir, $file->getName(), true);

                (new ItTicketAttachmentsModel)->create([
                    'ticket_id' => $ticketId,
                    'path' => $path,
                    'filename' => $file->getName(),
                    'created' => date('Y-m-d H:i:s'),
                    'uploader' => $uploaderId,
                ]);
            }
        }
    }

    public function addComment(int $ticketId, string $comment, int $creatorId): bool
    {
        $ticketModel = new ItTicketsModel();
        $ticket = $ticketModel->getById($ticketId);

        if (!$ticket) {
            throw new \RuntimeException('Ticket nem található: ' . $ticketId);
        }

        $comment = (new \App\Services\ItTicketInlineImageService())
            ->normalizeStoredHtml($comment, $ticketId, $creatorId);

        $data = [
            'ticket_id' => $ticketId,
            'note' => $comment,
            'creator' => $creatorId,
            'created' => new Time('now'),
        ];

        if (!(new ItTicketNotesModel())->create($data)) {
            return false;
        }

        if ($ticket->status === 'waiting_for_sender' && (int) $creatorId === (int) $ticket->sender_id) {
            $creatorUser = (new UserModel())->getById($creatorId);

            $note = '<strong>Állapot módosítás:</strong><br>'
                . 'Új állapot: folyamatban<br>'
                . 'Felhasználó: ' . ($creatorUser->name ?? '') . ' (' . ($creatorUser->antraid ?? '') . ')';

            $this->createSystemNote($ticket->id, $note);

            $ticketModel->update($ticket->id, [
                'status' => 'inprogress',
            ]);
        }

        $emailsString = '';

        $participants = $ticket->participants;

        if ($participants) {
            $participants = json_decode($participants, true);

            if (!empty($participants) && is_array($participants)) {
                $users = (new UserModel())->whereIn('id', $participants)->findAll();

                if (empty($users)) {
                    log_message('error', 'Nincsenek találatok a következő ID-kra: ' . implode(',', $participants));
                } else {
                    $emails = array_map(static function ($user) {
                        return $user->email;
                    }, $users);

                    $emailsString = implode(',', $emails);
                }
            }
        }

        $creatorUser = (new UserModel())->getById($creatorId);
        helper('basic');
        $emailData = \getEmailShortCodes();
        $emailData['creator'] = ($creatorUser->name ?? '') . ' (' . ($creatorUser->antraid ?? '') . ')';
        $emailData['task_number'] = '<a href="https://intranet.miellgroup.com/it_tickets/view/' . $ticket->id . '">' . $ticket->task_number . '</a>';
        $emailData['date'] = new Time('now');
        $emailData['note'] = $comment;

        $responsibleEmail = '';

        if ((int) $ticket->responsible !== (int) $ticket->sender_id) {
            $responsibleEmail = (string) (new UserModel())->getRowById($ticket->responsible, 'email');
        }

        $cc = trim($emailsString . ($responsibleEmail ? ', ' . $responsibleEmail : ''), ', ');
        (new TicketEmailService())->sendTemplate(
            $ticket->email,
            $cc ?: null,
            'MIELL munkalap: ' . $ticket->task_number . ' - új jegyzet',
            'it_tickets_new_note',
            $emailData
        );

        return true;
    }
    private function sendEmail($to, $cc = false, string $subject, string $html): void
    {
        if (!$to || !$subject || !$html) {
            return;
        }

        (new TicketEmailService())->send($to, $cc ?: null, $subject, $html);
    }

    private function createSystemNote($ticketId, $note): void
    {
        if ($ticketId && $note) {
            (new ItTicketNotesModel())->create([
                'ticket_id' => $ticketId,
                'note' => $note,
                'creator' => 0,
                'created' => new Time('now'),
            ]);
        }
    }
}
