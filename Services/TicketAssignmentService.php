<?php

namespace App\Modules\ItTickets\Services;

use App\Models\BasicdataModel;
use App\Modules\ItTickets\Models\ItTicketCategoriesModel;
use App\Modules\ItTickets\Models\ItTicketNotesModel;
use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

class TicketAssignmentService
{
    private const TICKET_URL = 'https://intranet.miellgroup.com/it_tickets/view/';

    private ItTicketsModel $ticketsModel;
    private ItTicketNotesModel $notesModel;
    private BasicdataModel $basicdataModel;
    private UserModel $userModel;
    private TicketEmailService $ticketEmailService;
    private ItTicketCategoriesModel $categoryModel;

    public function __construct(
        ?ItTicketsModel $ticketsModel = null,
        ?ItTicketNotesModel $notesModel = null,
        ?BasicdataModel $basicdataModel = null,
        ?UserModel $userModel = null,
        ?ItTicketCategoriesModel $categoryModel = null,
        ?TicketEmailService $ticketEmailService = null
    ) {
        $this->ticketsModel = $ticketsModel ?? new ItTicketsModel();
        $this->notesModel = $notesModel ?? new ItTicketNotesModel();
        $this->basicdataModel = $basicdataModel ?? new BasicdataModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->categoryModel = $categoryModel ?? new ItTicketCategoriesModel();
        $this->ticketEmailService = $ticketEmailService ?? new TicketEmailService();
    }

    public function changeResponsible(int $ticketId, int $responsibleId, int $actorId, string $actorAntraId): array
    {
        $ticket = $this->ticketsModel->getById($ticketId);
        if (!$ticket) {
            return [
                'status' => 'error',
                'message' => 'A munkalap nem található.',
                'data' => null,
            ];
        }

        $areaResponsibleId = (int) ($this->basicdataModel->getRowById($ticket->area, 'responsible') ?? 0);

        $data = [
            'responsible' => $responsibleId,
            'status' => 'todo',
            'validator' => ((int) $ticket->sender_id === $responsibleId)
                ? ($areaResponsibleId ?: (int) $ticket->sender_id)
                : (int) $ticket->sender_id,
        ];

        if ($this->ticketsModel->getRowById($ticketId, 'todo_date') === null) {
            $data['todo_date'] = new Time('now');
            $data['todo_creator'] = $actorId;
        }

        $this->createSystemNote(
            $ticketId,
            '<strong>Állapot módosítás:</strong><br>Új állapot: teendő<br>Felhasználó: '
            . $this->getUserLabel($actorId, $actorAntraId)
        );

        if (!$this->ticketsModel->update($ticketId, $data)) {
            return [
                'status' => 'error',
                'message' => 'A felelős módosítása nem sikerült.',
                'data' => null,
            ];
        }

        $updatedTicket = $this->ticketsModel->getById($ticketId);
        $this->sendResponsibleSelectedEmail($updatedTicket, $responsibleId);

        return [
            'status' => 'success',
            'message' => 'Felelős sikeresen módosítva.',
            'data' => $updatedTicket,
        ];
    }

    public function changeArea(int $ticketId, int $newAreaId, int $actorId, string $actorName, string $actorAntraId): array
    {
        $ticket = $this->ticketsModel->getById($ticketId);
        if (!$ticket) {
            return [
                'status' => 'error',
                'message' => 'A munkalap nem található.',
                'data' => null,
            ];
        }

        $this->createSystemNote(
            $ticketId,
            '<strong>Terület módosítás:</strong><br>'
            . 'Régi terület: ' . esc($ticket->areaName) . '<br>'
            . 'Új terület: ' . esc($this->basicdataModel->getRowById($newAreaId, 'name')) . '<br>'
            . 'Felhasználó: ' . esc($actorName) . ' (' . esc($actorAntraId) . ')'
        );

        $updated = $this->ticketsModel->update($ticketId, [
            'responsible' => null,
            'status' => 'planned',
            'area' => $newAreaId,
            'category' => 3,
        ]);

        if (!$updated) {
            return [
                'status' => 'error',
                'message' => 'A terület módosítása nem sikerült.',
                'data' => null,
            ];
        }

        $updatedTicket = $this->ticketsModel->getById($ticketId);
        $emails = $this->basicdataModel->getAreaEmails($updatedTicket->area);

        if (empty($emails)) {
            return [
                'status' => 'warning',
                'message' => 'A terület módosult, de az új területhez nincs megadva e-mail cím, így értesítés nem lett küldve.',
                'data' => $updatedTicket,
            ];
        }

        $sent = $this->sendAreaSelectedEmail($updatedTicket, $emails);

        return [
            'status' => 'success',
            'message' => $sent
                ? 'Terület sikeresen módosítva.'
                : 'Terület módosult, de az értesítés küldése sikertelen.',
            'data' => $updatedTicket,
        ];
    }

    private function createSystemNote(int $ticketId, string $note): void
    {
        if ($ticketId <= 0 || $note === '') {
            return;
        }

        $this->notesModel->create([
            'ticket_id' => $ticketId,
            'note' => $note,
            'creator' => 0,
            'created' => new Time('now'),
        ]);
    }
    private function sendResponsibleSelectedEmail(object $ticket, int $responsibleId): void
    {
        $data = getEmailShortCodes();
        $data['name'] = $this->userModel->getRowById($responsibleId, 'name');
        $data['email'] = $ticket->email;
        $data['phone'] = $ticket->phone;
        $data['subject'] = $ticket->name;
        $data['task_number'] = $ticket->task_number;
        $data['description'] = $ticket->description;
        $data['category'] = ucfirst((string) $this->categoryModel->getRowById($ticket->category, 'name'));
        $data['link'] = self::TICKET_URL . $ticket->id;

        $this->ticketEmailService->sendTemplate(
            $this->userModel->getRowById($responsibleId, 'email'),
            null,
            'MIELL munkalap: ' . $ticket->task_number,
            'it_tickets_select_responsible',
            $data
        );
    }
    private function sendAreaSelectedEmail(object $ticket, array $emails): bool
    {
        $data = getEmailShortCodes();
        $data['email'] = $ticket->email;
        $data['phone'] = $ticket->phone;
        $data['subject'] = $ticket->name;
        $data['task_number'] = $ticket->task_number;
        $data['description'] = $ticket->description;
        $data['category'] = $ticket->categoryName;
        $data['link'] = self::TICKET_URL . $ticket->id;

        return $this->ticketEmailService->sendTemplate(
            $emails,
            null,
            'MIELL munkalap: ' . $ticket->task_number,
            'it_tickets_it',
            $data
        );
    }
    private function sendEmail($to, $cc, string $subject, string $html): bool
    {
        return $this->ticketEmailService->send($to, $cc, $subject, $html);
    }

    private function getUserLabel(int $userId, string $fallbackAntraId): string
    {
        $name = $this->userModel->getRowById($userId, 'name');
        $antraId = $this->userModel->getRowById($userId, 'antraid') ?: $fallbackAntraId;

        return $name . ' (' . $antraId . ')';
    }
}
