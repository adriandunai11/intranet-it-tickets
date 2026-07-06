<?php

namespace App\Modules\ItTickets\Services;

use App\Models\ItTicketNotesModel;

class TicketCommentService
{
    private ItTicketNotesModel $notesModel;
    private \App\Services\ItTicketCreator $ticketCreator;

    public function __construct(
        ?ItTicketNotesModel $notesModel = null,
        ?\App\Services\ItTicketCreator $ticketCreator = null
    ) {
        $this->notesModel = $notesModel ?? new ItTicketNotesModel();
        $this->ticketCreator = $ticketCreator ?? new \App\Services\ItTicketCreator();
    }

    public function add(int $ticketId, string $comment, int $creatorId): bool
    {
        if ($ticketId <= 0 || trim($comment) === '' || $creatorId <= 0) {
            return false;
        }

        return (bool) $this->ticketCreator->addComment($ticketId, $comment, $creatorId);
    }

    public function find(int $noteId): ?object
    {
        $note = $this->notesModel->getById($noteId);

        return $note ?: null;
    }

    public function delete(int $noteId): array
    {
        $note = $this->find($noteId);

        if (!$note) {
            return [
                'status' => false,
                'message' => 'A jegyzet nem található.',
                'ticket_id' => null,
            ];
        }

        $ticketId = (int) $note->ticket_id;
        $deleted = $this->notesModel->delete($noteId);

        return [
            'status' => (bool) $deleted,
            'message' => $deleted
                ? 'Jegyzet sikeresen törölve.'
                : 'A jegyzet törlése nem sikerült.',
            'ticket_id' => $ticketId,
        ];
    }
}
