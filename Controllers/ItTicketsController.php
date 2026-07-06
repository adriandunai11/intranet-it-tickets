<?php

namespace App\Modules\ItTickets\Controllers;

use App\Models\ItTicketsModel;
use App\Modules\ItTickets\Services\RecurringTicketService;
use App\Modules\ItTickets\Services\TicketAttachmentService;
use App\Modules\ItTickets\Services\TicketCommentService;

class ItTicketsController extends \App\Controllers\It_tickets
{
    public function runRecurringTaskNow($id)
    {
        $this->permissionCheck('view_recurring_it_tickets');
        postAllowed();

        $result = (new RecurringTicketService())->generateOne((int) $id);

        return $this->response->setJSON([
            'status' => $result['status'],
            'message' => $result['message'],
        ]);
    }

    public static function generateRecurringTasks(): bool
    {
        return (new RecurringTicketService())->generateDueTasks();
    }

    public function testRecurringTasks()
    {
        $this->permissionCheck('manage_it_tickets');

        (new RecurringTicketService())->generateDueTasks();

        return redirect()->to('it_tickets')->with('sSuccess', 'Ismétlődő feladatok generálása lefutott.');
    }

    public function addComment($ticket_id)
    {
        $this->permissionCheck('create_it_ticket');

        $ticketId = (int) $ticket_id;
        $ticket = (new ItTicketsModel())->getById($ticketId);

        if (!$ticket) {
            return redirect()->to('it_tickets');
        }

        $perm = getTicketPermissions($ticket);
        if (empty($perm['can_add_comment_or_file'])) {
            return $this->response->redirect('/errors/denied');
        }

        postAllowed();

        try {
            $ok = (new TicketCommentService())->add(
                $ticketId,
                (string) post('comment'),
                (int) logged('id')
            );

            if ($ok) {
                return redirect()->to('it_tickets/view/' . $ticketId)
                    ->with('sSuccess', 'Jegyzet sikeresen létrehozva.');
            }

            return redirect()->to('it_tickets/view/' . $ticketId)
                ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
        } catch (\Throwable $e) {
            log_message('error', 'Ticket comment create failed: ' . $e->getMessage());

            return redirect()->to('it_tickets/view/' . $ticketId)
                ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
        }
    }

    public function deleteComment($id)
    {
        $noteId = (int) $id;
        $service = new TicketCommentService();
        $note = $service->find($noteId);

        if (!$note) {
            return redirect()->to('it_tickets');
        }

        if ((int) $note->creator !== (int) logged('id') && !(hasPermissions('manage_it_tickets') && (int) $note->creator !== 0)) {
            return $this->response->redirect('/errors/denied');
        }

        $result = $service->delete($noteId);
        $ticketId = (int) ($result['ticket_id'] ?? $note->ticket_id);

        if ($result['status']) {
            return redirect()->to('it_tickets/view/' . $ticketId)
                ->with('sSuccess', $result['message']);
        }

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }

    public function addAttachment($ticket_id)
    {
        $ticketId = (int) $ticket_id;
        $ticket = (new ItTicketsModel())->getById($ticketId);
        $perm = getTicketPermissions($ticket);

        if (empty($perm['can_add_comment_or_file'])) {
            return $this->response->redirect('/errors/denied');
        }

        postAllowed();

        if (!$this->validate([
            'files' => [
                'max_size[files,25600]',
            ],
        ])) {
            return redirect()->to('it_tickets/view/' . $ticketId)
                ->withInput()
                ->with('validation_add_attachment', $this->validator->getErrors());
        }

        $files = $this->request->getFileMultiple('files');
        $uploaded = (new TicketAttachmentService())->uploadMultiple(
            $ticketId,
            $files ?: [],
            (int) logged('id')
        );

        if ($uploaded) {
            return redirect()->to('it_tickets/view/' . $ticketId)
                ->with('sSuccess', 'Sikeres állomány feltöltés.');
        }

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }

    public function deleteAttachment($id)
    {
        $attachmentId = (int) $id;
        $service = new TicketAttachmentService();
        $attachment = $service->find($attachmentId);

        if (!$attachment) {
            return redirect()->to('it_tickets');
        }

        if ((int) $attachment->uploader !== (int) logged('id') && !hasPermissions('manage_it_tickets')) {
            return $this->response->redirect('/errors/denied');
        }

        $result = $service->delete($attachmentId);
        $ticketId = (int) ($result['ticket_id'] ?? $attachment->ticket_id);

        if ($result['status']) {
            return redirect()->to('it_tickets/view/' . $ticketId)
                ->with('sSuccess', $result['message']);
        }

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }
}
