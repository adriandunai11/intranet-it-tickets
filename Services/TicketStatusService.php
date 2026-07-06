<?php

namespace App\Modules\ItTickets\Services;

use App\Modules\ItTickets\Models\ItTicketNotesModel;
use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

class TicketStatusService
{
    private ItTicketsModel $ticketsModel;
    private ItTicketNotesModel $notesModel;
    private UserModel $userModel;
    private TicketEmailService $ticketEmailService;

    public function __construct(
        ?ItTicketsModel $ticketsModel = null,
        ?ItTicketNotesModel $notesModel = null,
        ?UserModel $userModel = null,
        ?TicketEmailService $ticketEmailService = null
    ) {
        $this->ticketsModel = $ticketsModel ?? new ItTicketsModel();
        $this->notesModel = $notesModel ?? new ItTicketNotesModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->ticketEmailService = $ticketEmailService ?? new TicketEmailService();
    }

    public function changeStatus(int $ticketId, string $status, int $actorId, string $actorAntraId): array
    {
        $ticket = $this->ticketsModel->getById($ticketId);
        if (!$ticket) {
            return [
                'status' => 'error',
                'data' => null,
                'message' => 'A munkalap nem található.',
            ];
        }

        $data = [
            'status' => $status,
            'is_validated' => 0,
        ];

        $ccEmails = $this->buildCcEmails($ticket);

        switch ($status) {
            case 'todo':
                $this->addStatusNote($ticketId, 'teendő', $actorId, $actorAntraId);
                if ($ticket->todo_date === null) {
                    $data['todo_date'] = new Time('now');
                    $data['todo_creator'] = $actorId;
                }
                break;

            case 'project':
                $this->addStatusNote($ticketId, 'projekt', $actorId, $actorAntraId);
                if ($ticket->project_date === null) {
                    $data['project_date'] = new Time('now');
                    $data['project_creator'] = $actorId;
                }
                $this->ticketEmailService->sendStatusChangeEmail($ticket, $ccEmails, 'projektre', 'projekt', 'it_tickets_status_update');
                break;

            case 'inprogress':
                $this->addStatusNote($ticketId, 'folyamatban', $actorId, $actorAntraId);
                if ($ticket->inprogress_date === null) {
                    $data['inprogress_date'] = new Time('now');
                    $data['inprogress_creator'] = $actorId;
                }
                $this->ticketEmailService->sendStatusChangeEmail($ticket, $ccEmails, 'folyamatbanra', 'folyamatban', 'it_tickets_status_update');
                break;

            case 'waiting_for_sender':
                $this->addStatusNote($ticketId, 'bejelentő válaszára vár', $actorId, $actorAntraId);
                if ($ticket->waiting_date === null) {
                    $data['waiting_date'] = new Time('now');
                    $data['waiting_creator'] = $actorId;
                }
                $this->ticketEmailService->sendStatusChangeEmail($ticket, $ccEmails, 'bejelentő válaszára vár értékre', 'bejelentő válaszára vár', 'it_tickets_status_update');
                break;

            case 'finished':
                $this->addStatusNote($ticketId, 'befejezett', $actorId, $actorAntraId);
                if ($ticket->finished_date === null) {
                    $data['finished_date'] = new Time('now');
                    $data['finished_creator'] = $actorId;
                }
                $data['sent_to_validation'] = new Time('now');
                $this->ticketEmailService->sendStatusChangeEmail($ticket, $ccEmails, 'befejezettre', 'befejezett', 'it_tickets_send_to_validation');
                break;
        }

        $updated = $this->ticketsModel->update($ticketId, $data);

        return [
            'status' => $updated ? 'success' : 'error',
            'data' => $updated ? $this->ticketsModel->where('id', $ticketId)->first() : $data,
            'message' => $updated ? 'Státusz sikeresen módosítva.' : 'A státusz módosítása nem sikerült.',
        ];
    }

    private function addStatusNote(int $ticketId, string $statusLabel, int $actorId, string $actorAntraId): void
    {
        $actorName = $this->userModel->getRowById($actorId, 'name');
        $actorAntraId = $this->userModel->getRowById($actorId, 'antraid') ?: $actorAntraId;

        $this->notesModel->create([
            'ticket_id' => $ticketId,
            'note' => '<strong>Állapot módosítás:</strong><br>Új állapot: ' . $statusLabel . '<br>Felhasználó: ' . $actorName . ' (' . $actorAntraId . ')',
            'creator' => 0,
            'created' => new Time('now'),
        ]);
    }

    private function buildCcEmails(object $ticket): string
    {
        $emails = [];
        $participantEmails = $this->getParticipantEmails($ticket);

        if ($participantEmails !== '') {
            $emails[] = $participantEmails;
        }

        $responsibleEmail = $this->getResponsibleCcEmail($ticket);
        if ($responsibleEmail !== '') {
            $emails[] = $responsibleEmail;
        }

        return implode(', ', $emails);
    }

    private function getParticipantEmails(object $ticket): string
    {
        if (empty($ticket->participants)) {
            return '';
        }

        $participants = json_decode($ticket->participants, true);
        if (empty($participants) || !is_array($participants)) {
            return '';
        }

        $users = $this->userModel->whereIn('id', $participants)->findAll();
        if (empty($users)) {
            log_message('error', 'Nincsenek találatok a következő ID-kra: ' . implode(',', $participants));
            return '';
        }

        $emails = array_filter(array_map(static function ($user): ?string {
            return $user->email ?? null;
        }, $users));

        return implode(',', $emails);
    }

    private function getResponsibleCcEmail(object $ticket): string
    {
        if ((int) $ticket->responsible === (int) $ticket->sender_id) {
            return '';
        }

        return (string) $this->userModel->getRowById($ticket->responsible, 'email');
    }
}
