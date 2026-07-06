<?php

namespace App\Modules\ItTickets\Services;

use App\Models\BasicdataModel;
use App\Models\UserModel;
use App\Modules\ItTickets\Models\ItTicketNotesModel;
use App\Modules\ItTickets\Models\ItTicketsModel;
use CodeIgniter\I18n\Time;

class TicketValidationService
{
    private const TICKET_URL = 'https://intranet.miellgroup.com/it_tickets/view/';

    private ItTicketsModel $ticketsModel;
    private ItTicketNotesModel $notesModel;
    private UserModel $userModel;
    private BasicdataModel $basicdataModel;
    private TicketEmailService $ticketEmailService;

    public function __construct(
        ?ItTicketsModel $ticketsModel = null,
        ?ItTicketNotesModel $notesModel = null,
        ?UserModel $userModel = null,
        ?BasicdataModel $basicdataModel = null,
        ?TicketEmailService $ticketEmailService = null
    ) {
        $this->ticketsModel = $ticketsModel ?? new ItTicketsModel();
        $this->notesModel = $notesModel ?? new ItTicketNotesModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->basicdataModel = $basicdataModel ?? new BasicdataModel();
        $this->ticketEmailService = $ticketEmailService ?? new TicketEmailService();
    }

    public function validate(int $ticketId, bool $isValidated, ?string $comment = null): array
    {
        $ticket = $this->ticketsModel->getById($ticketId);
        if (!$ticket) {
            return [
                'status' => false,
                'message' => 'A munkalap nem található.',
                'data' => null,
            ];
        }

        $data = [
            'is_validated' => $isValidated ? 1 : 0,
            'validation_date' => $isValidated ? new Time('now') : null,
        ];

        if (!$isValidated) {
            $data['status'] = 'inprogress';
        }

        $updated = $this->ticketsModel->update($ticketId, $data);
        if (!$updated) {
            return [
                'status' => false,
                'message' => 'A validálás mentése nem sikerült.',
                'data' => $data,
            ];
        }

        $this->sendValidationEmail($ticket, $isValidated, $comment);
        $this->createValidationNote($ticketId, $isValidated, $comment);

        return [
            'status' => true,
            'message' => 'Sikeres validálás.',
            'data' => $this->ticketsModel->where('id', $ticketId)->first(),
        ];
    }

    private function sendValidationEmail(object $ticket, bool $isValidated, ?string $comment): void
    {
        $emailData = getEmailShortCodes();
        $emailData['name'] = $this->userModel->getRowById($ticket->responsible, 'name');
        $emailData['sender'] = $this->userModel->getRowById($ticket->sender_id, 'name')
            . ' (' . $this->userModel->getRowById($ticket->sender_id, 'antraid') . ')';
        $emailData['task_number'] = '<a href="' . self::TICKET_URL . $ticket->id . '">'
            . $ticket->task_number . ' - ' . $ticket->name . '</a>';
        $emailData['comment'] = $comment;

        if ($isValidated) {
            $this->ticketEmailService->sendTemplate(
                $this->userModel->getRowById($ticket->responsible, 'email'),
                null,
                'MIELL munkalap: ' . $ticket->task_number . ' - validált',
                'it_ticket_validate',
                $emailData
            );
            return;
        }

        $areaResponsibleId = $this->basicdataModel->getRowById($ticket->area, 'responsible');
        $areaResponsibleEmail = $this->userModel->getRowById($areaResponsibleId, 'email');

        $this->ticketEmailService->sendTemplate(
            $areaResponsibleEmail,
            $this->userModel->getRowById($ticket->responsible, 'email'),
            'MIELL munkalap: ' . $ticket->task_number . ' - nem validált',
            'it_ticket_no_validate',
            $emailData
        );
    }

    private function createValidationNote(int $ticketId, bool $isValidated, ?string $comment): void
    {
        if (trim((string) $comment) === '') {
            return;
        }

        $validationStatus = $isValidated ? 'Igen' : 'Nem';
        $this->notesModel->create([
            'ticket_id' => $ticketId,
            'note' => 'Validáció: ' . $validationStatus . '<br>Validáció megjegyzése: ' . $comment,
            'creator' => 0,
            'created' => new Time('now'),
        ]);
    }
}
