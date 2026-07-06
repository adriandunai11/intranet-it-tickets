<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Modules\ItTickets\Models\ItTicketNotesModel;
use App\Services\ItTicketInlineImageService;

class ItTicketNormalizeInlineImages extends BaseCommand
{
    protected $group = 'IT Tickets';
    protected $name = 'it-tickets:normalize-inline-images';
    protected $description = 'Base64 inline képek mentése fájlba ticket description és note mezőkből.';

    protected $usage = 'it-tickets:normalize-inline-images [--ticket=123] [--limit=200] [--dry-run]';

    protected $options = [
        '--ticket' => 'Csak egy adott ticket ID feldolgozása.',
        '--limit' => 'Batch módnál maximum feldolgozandó rekordok száma. Alapértelmezett: 200.',
        '--dry-run' => 'Csak ellenőrzés, adatbázis és fájl módosítás nélkül.',
    ];

    public function run(array $params)
    {
        $ticketModel = new ItTicketsModel();
        $noteModel = new ItTicketNotesModel();
        $imageService = new ItTicketInlineImageService();

        $ticketId = $this->getCliIntOption('ticket', $params, 0);
        $limit = $this->getCliIntOption('limit', $params, 200);
        $dryRun = $this->hasCliFlag('dry-run', $params);

        CLI::write('Inline base64 képek normalizálása indul...', 'green');
        CLI::write('Ticket: ' . ($ticketId > 0 ? '#' . $ticketId : 'összes / batch'));
        CLI::write('Limit: ' . $limit);
        CLI::write('Dry-run: ' . ($dryRun ? 'igen' : 'nem'));
        CLI::newLine();

        if ($ticketId > 0) {
            $this->processSingleTicket(
                $ticketId,
                $ticketModel,
                $noteModel,
                $imageService,
                $dryRun
            );

            return;
        }

        $this->processBatch(
            $limit,
            $ticketModel,
            $noteModel,
            $imageService,
            $dryRun
        );
    }

    private function processSingleTicket(
        int $ticketId,
        ItTicketsModel $ticketModel,
        ItTicketNotesModel $noteModel,
        ItTicketInlineImageService $imageService,
        bool $dryRun
    ): void {
        $ticket = $ticketModel->getById($ticketId);

        if (!$ticket) {
            CLI::error("Ticket nem található: #{$ticketId}");
            return;
        }

        $ticketUpdated = 0;
        $noteUpdated = 0;

        CLI::write("Ticket #{$ticketId} feldolgozása...", 'yellow');

        $oldDescription = (string) ($ticket->description ?? '');

        if ($this->containsProcessableImageData($oldDescription)) {
            if ($dryRun) {
                CLI::write("DRY ticket #{$ticketId}: description mezőben base64 / MIME image adat található.", 'cyan');
            } else {
                try {
                    $newDescription = $this->normalizeHtml(
                        $oldDescription,
                        $ticketId,
                        isset($ticket->sender_id) ? (int) $ticket->sender_id : null,
                        $imageService
                    );

                    if ($newDescription !== $oldDescription) {
                        $ticketModel->update($ticketId, [
                            'description' => $newDescription,
                        ]);

                        $ticketUpdated++;
                        CLI::write("OK ticket #{$ticketId}: description frissítve.", 'green');
                    } else {
                        CLI::write("SKIP ticket #{$ticketId}: description nem változott.", 'yellow');
                    }
                } catch (\Throwable $e) {
                    CLI::error("HIBA ticket #{$ticketId}: " . $e->getMessage());
                }
            }
        } else {
            CLI::write("Ticket #{$ticketId}: description mezőben nincs feldolgozható image adat.", 'yellow');
        }

        $notes = $noteModel
            ->where('ticket_id', $ticketId)
            ->orderBy('id', 'ASC')
            ->findAll();

        CLI::write('Note-ok száma: ' . count($notes));

        foreach ($notes as $note) {
            $noteId = (int) $note->id;
            $oldNote = (string) ($note->note ?? '');

            if (!$this->containsProcessableImageData($oldNote)) {
                continue;
            }

            if ($dryRun) {
                CLI::write("DRY note #{$noteId}, ticket #{$ticketId}: base64 / MIME image adat található.", 'cyan');
                continue;
            }

            try {
                $newNote = $this->normalizeHtml(
                    $oldNote,
                    $ticketId,
                    isset($note->creator) ? (int) $note->creator : null,
                    $imageService
                );

                if ($newNote !== $oldNote) {
                    $noteModel->update($noteId, [
                        'note' => $newNote,
                    ]);

                    $noteUpdated++;
                    CLI::write("OK note #{$noteId}: frissítve.", 'green');
                } else {
                    CLI::write("SKIP note #{$noteId}: nem változott.", 'yellow');
                }
            } catch (\Throwable $e) {
                CLI::error("HIBA note #{$noteId}: " . $e->getMessage());
            }
        }

        CLI::newLine();
        CLI::write("Kész. ticket_updated={$ticketUpdated}, note_updated={$noteUpdated}", 'green');
    }

    private function processBatch(
        int $limit,
        ItTicketsModel $ticketModel,
        ItTicketNotesModel $noteModel,
        ItTicketInlineImageService $imageService,
        bool $dryRun
    ): void {
        $ticketUpdated = 0;
        $noteUpdated = 0;

        $tickets = $ticketModel
            ->groupStart()
            ->like('description', 'data:image/', 'both')
            ->orLike('description', 'Content-Type: image/', 'both')
            ->orLike('description', 'Content-Transfer-Encoding: base64', 'both')
            ->orLike('description', 'P {margin-top:0;margin-bottom:0;}', 'both')
            ->orLike('description', 'margin-top:0', 'both')
            ->orLike('description', '<style', 'both')
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->findAll($limit);

        CLI::write('Talált ticket description: ' . count($tickets));

        foreach ($tickets as $ticket) {
            $ticketId = (int) $ticket->id;
            $old = (string) ($ticket->description ?? '');

            if (!$this->containsProcessableImageData($old)) {
                continue;
            }

            if ($dryRun) {
                CLI::write("DRY ticket #{$ticketId}: base64 / MIME image adat található.", 'cyan');
                continue;
            }

            try {
                $new = $this->normalizeHtml(
                    $old,
                    $ticketId,
                    isset($ticket->sender_id) ? (int) $ticket->sender_id : null,
                    $imageService
                );

                if ($new !== $old) {
                    $ticketModel->update($ticketId, [
                        'description' => $new,
                    ]);

                    $ticketUpdated++;
                    CLI::write("OK ticket #{$ticketId}", 'green');
                }
            } catch (\Throwable $e) {
                CLI::error("HIBA ticket #{$ticketId}: " . $e->getMessage());
            }
        }

        CLI::newLine();

        $notes = $noteModel
            ->groupStart()
            ->like('note', 'data:image/', 'both')
            ->orLike('note', 'Content-Type: image/', 'both')
            ->orLike('note', 'Content-Transfer-Encoding: base64', 'both')
            ->orLike('note', 'P {margin-top:0;margin-bottom:0;}', 'both')
            ->orLike('note', 'margin-top:0', 'both')
            ->orLike('note', '<style', 'both')
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->findAll($limit);

        CLI::write('Talált note: ' . count($notes));

        foreach ($notes as $note) {
            $noteId = (int) $note->id;
            $ticketId = (int) $note->ticket_id;
            $old = (string) ($note->note ?? '');

            if ($ticketId <= 0 || !$this->containsProcessableImageData($old)) {
                continue;
            }

            if ($dryRun) {
                CLI::write("DRY note #{$noteId}, ticket #{$ticketId}: base64 / MIME image adat található.", 'cyan');
                continue;
            }

            try {
                $new = $this->normalizeHtml(
                    $old,
                    $ticketId,
                    isset($note->creator) ? (int) $note->creator : null,
                    $imageService
                );

                if ($new !== $old) {
                    $noteModel->update($noteId, [
                        'note' => $new,
                    ]);

                    $noteUpdated++;
                    CLI::write("OK note #{$noteId} ticket #{$ticketId}", 'green');
                }
            } catch (\Throwable $e) {
                CLI::error("HIBA note #{$noteId}: " . $e->getMessage());
            }
        }

        CLI::newLine();
        CLI::write("Kész. ticket_updated={$ticketUpdated}, note_updated={$noteUpdated}", 'green');
    }

    private function normalizeHtml(
        string $html,
        int $ticketId,
        ?int $uploaderId,
        ItTicketInlineImageService $imageService
    ): string {
        return $imageService->normalizeStoredHtml($html, $ticketId, $uploaderId);
    }

    private function containsProcessableImageData(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        return str_contains($html, 'data:image/')
            || str_contains($html, 'Content-Type: image/')
            || str_contains($html, 'Content-Transfer-Encoding: base64')
            || str_contains($html, 'P {margin-top:0;margin-bottom:0;}')
            || str_contains($html, 'margin-top:0')
            || str_contains($html, '<style');
    }

    private function getCliIntOption(string $name, array $params, int $default = 0): int
    {
        $value = CLI::getOption($name);

        if ($value !== null && $value !== false && $value !== '') {
            return (int) $value;
        }

        $prefix = '--' . $name . '=';

        foreach ($params as $index => $param) {
            $param = (string) $param;

            if (str_starts_with($param, $prefix)) {
                return (int) substr($param, strlen($prefix));
            }

            if ($param === '--' . $name && isset($params[$index + 1])) {
                return (int) $params[$index + 1];
            }
        }

        return $default;
    }

    private function hasCliFlag(string $name, array $params): bool
    {
        $value = CLI::getOption($name);

        if ($value !== null && $value !== false) {
            return true;
        }

        return in_array('--' . $name, array_map('strval', $params), true);
    }

    public function removeCssNoise(string $html): string
    {
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        $patterns = [
            '/^\s*P\s*\{\s*margin-top\s*:\s*0\s*;\s*margin-bottom\s*:\s*0\s*;\s*\}\s*$/imu',
            '/^\s*(BODY|HTML|DIV|SPAN|P|LI|UL|OL|TABLE|TD|TH)\s*\{[^}]*\}\s*$/imu',
            '/<style\b[^>]*>[\s\S]*?<\/style>/iu',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        $html = preg_replace("/\n{3,}/", "\n\n", $html);

        return trim($html);
    }
}