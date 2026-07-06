<?php

namespace App\Modules\ItTickets\Services;

use App\Models\BasicdataModel;
use App\Models\UserModel;
use App\Modules\ItTickets\Models\ItTicketCategoriesModel;
use App\Modules\ItTickets\Models\ItTicketsModel;

class TicketCreationNotificationService
{
    private const TICKET_URL = 'https://intranet.miellgroup.com/it_tickets/view/';

    private ItTicketsModel $ticketsModel;
    private ItTicketCategoriesModel $categoryModel;
    private BasicdataModel $basicdataModel;
    private UserModel $userModel;
    private TicketEmailService $ticketEmailService;

    public function __construct(
        ?ItTicketsModel $ticketsModel = null,
        ?ItTicketCategoriesModel $categoryModel = null,
        ?BasicdataModel $basicdataModel = null,
        ?UserModel $userModel = null,
        ?TicketEmailService $ticketEmailService = null
    ) {
        $this->ticketsModel = $ticketsModel ?? new ItTicketsModel();
        $this->categoryModel = $categoryModel ?? new ItTicketCategoriesModel();
        $this->basicdataModel = $basicdataModel ?? new BasicdataModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->ticketEmailService = $ticketEmailService ?? new TicketEmailService();
    }

    public function notifyCreated(int $ticketId, array $payload): void
    {
        $ticket = $this->ticketsModel->find($ticketId);
        if (!$ticket) {
            return;
        }

        $taskNumber = $ticket->task_number ?? '';
        $data = getEmailShortCodes();
        $data['name'] = $payload['sender_name'] ?? '';
        $data['email'] = $payload['sender_email'] ?? '';
        $data['phone'] = $payload['sender_phone'] ?? '';
        $data['subject'] = $payload['task_name'] ?? '';
        $data['task_number'] = '<a href="' . self::TICKET_URL . $ticketId . '">' . $taskNumber . '</a>';
        $data['description'] = $payload['description'] ?? '';
        $data['category'] = ucfirst((string) $this->categoryModel->getRowById((int) ($payload['category'] ?? 0), 'name'));
        $data['area'] = $this->basicdataModel->getRowById((int) ($payload['area'] ?? 0), 'name');

        $areaEmails = $this->basicdataModel->getRowById((int) ($payload['area'] ?? 0), 'email') ?: [];
        foreach ($areaEmails as $row) {
            $this->ticketEmailService->sendTemplate(
                $row,
                null,
                'MIELL munkalap: ' . $taskNumber,
                'it_tickets_it',
                $data
            );
        }

        $this->ticketEmailService->sendTemplate(
            $payload['sender_email'] ?? '',
            $this->getParticipantsEmails($payload['participants'] ?? []),
            'MIELL munkalap: ' . $taskNumber,
            'it_tickets_sender',
            $data
        );
    }

    private function getParticipantsEmails(array $participants): string
    {
        if (empty($participants)) {
            return '';
        }

        $users = $this->userModel->whereIn('id', $participants)->findAll();
        if (empty($users)) {
            log_message('error', 'Nincsenek találatok a következő ID-kra: ' . implode(',', $participants));
            return '';
        }

        return implode(',', array_filter(array_map(static function ($user): ?string {
            return $user->email ?? null;
        }, $users)));
    }
}
