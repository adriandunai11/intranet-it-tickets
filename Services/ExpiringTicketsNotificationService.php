<?php

namespace App\Modules\ItTickets\Services;

use App\Models\BasicdataModel;
use App\Modules\ItTickets\Models\ItTicketCategoriesModel;
use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Models\UserModel;
use CodeIgniter\CLI\CLI;

class ExpiringTicketsNotificationService
{
    private const FROM_EMAIL = 'intranet@miellgroup.com';
    private const FROM_NAME = 'MIELL Group - Intranet';
    private const TEMPLATE_CODE = 'it_tickets_expiring_tickets';
    private const ACTIVE_STATUSES = 'planned,todo,inprogress,project,waiting_for_sender';
    private const DAYS_BEFORE_DEADLINE = 1;
    private const TICKET_URL = 'https://intranet.miellgroup.com/it_tickets/view/';

    private ItTicketsModel $ticketsModel;
    private UserModel $userModel;
    private BasicdataModel $basicdataModel;
    private ItTicketCategoriesModel $categoryModel;
    private TicketEmailService $ticketEmailService;

    public function __construct(
        ?ItTicketsModel $ticketsModel = null,
        ?UserModel $userModel = null,
        ?BasicdataModel $basicdataModel = null,
        ?ItTicketCategoriesModel $categoryModel = null,
        ?TicketEmailService $ticketEmailService = null
    ) {
        $this->ticketsModel = $ticketsModel ?? new ItTicketsModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->basicdataModel = $basicdataModel ?? new BasicdataModel();
        $this->categoryModel = $categoryModel ?? new ItTicketCategoriesModel();
        $this->ticketEmailService = $ticketEmailService ?? new TicketEmailService();
    }

    public function send(): bool
    {
        $expiringTickets = $this->ticketsModel->getExpiringTickets(
            self::ACTIVE_STATUSES,
            self::DAYS_BEFORE_DEADLINE
        );

        if (empty($expiringTickets)) {
            $this->cliWrite('Expiring tickets: 0', 'yellow');
            return true;
        }

        $this->cliWrite('Expiring tickets: ' . count($expiringTickets), 'green');

        $groupedTickets = $this->groupTicketsByResponsibleAndArea($expiringTickets);
        if (empty($groupedTickets)) {
            return true;
        }

        foreach ($groupedTickets as $responsibleId => $areaBuckets) {
            $toEmail = $this->getUserEmail((int) $responsibleId);
            if (!$toEmail) {
                continue;
            }

            foreach ($areaBuckets as $areaId => $tickets) {
                $areaName = $this->getAreaName((int) $areaId);
                $ccEmails = $this->getCcEmails((int) $areaId, $toEmail);
                $subject = 'MIELL - Lejáró határidejű munkalapjaid (' . $areaName . ')';

                $sent = $this->ticketEmailService->sendTemplate(
                    $toEmail,
                    $ccEmails,
                    $subject,
                    self::TEMPLATE_CODE,
                    ['items' => $this->buildTemplateItems($tickets)],
                    self::FROM_EMAIL,
                    self::FROM_NAME
                );

                $this->cliWrite(
                    'To: ' . $toEmail . (empty($ccEmails) ? '' : ' | Cc: ' . implode(',', $ccEmails)) . ' (' . $areaName . ')',
                    $sent ? 'green' : 'red'
                );
            }
        }

        return true;
    }

    private function groupTicketsByResponsibleAndArea(array $tickets): array
    {
        $grouped = [];

        foreach ($tickets as $ticket) {
            $responsibleId = (int) ($ticket['responsible'] ?? 0);
            $areaId = (int) ($ticket['area'] ?? 0);

            if ($responsibleId <= 0 || $areaId <= 0) {
                continue;
            }

            $grouped[$responsibleId][$areaId][] = $ticket;
        }

        return $grouped;
    }

    private function getUserEmail(int $userId): ?string
    {
        $user = $this->userModel->getById($userId);
        $email = $user->email ?? null;

        if (!$email) {
            return null;
        }

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function getAreaName(int $areaId): string
    {
        return (string) ($this->basicdataModel->getRowById($areaId, 'name') ?? ('Terület #' . $areaId));
    }

    private function getCcEmails(int $areaId, string $toEmail): array
    {
        $areaResponsibleId = (int) ($this->basicdataModel->getRowById($areaId, 'responsible') ?? 0);
        if ($areaResponsibleId <= 0) {
            return [];
        }

        $areaResponsibleEmail = $this->userModel->getRowById($areaResponsibleId, 'email');
        if (!$areaResponsibleEmail) {
            return [];
        }

        $areaResponsibleEmail = strtolower(trim($areaResponsibleEmail));
        if (!filter_var($areaResponsibleEmail, FILTER_VALIDATE_EMAIL) || $areaResponsibleEmail === $toEmail) {
            return [];
        }

        return [$areaResponsibleEmail];
    }

    private function buildTemplateItems(array $tickets): array
    {
        $items = [];

        foreach ($tickets as $ticket) {
            $items[] = [
                'name' => $this->buildTicketLink($ticket),
                'category' => $this->categoryModel->getRowById($ticket['category'], 'name'),
            ];
        }

        return $items;
    }

    private function buildTicketLink(array $ticket): string
    {
        $ticketId = (int) ($ticket['id'] ?? 0);
        $taskNumber = esc($ticket['task_number'] ?? '');
        $taskName = esc($ticket['name'] ?? '');

        return '<a href="' . self::TICKET_URL . $ticketId . '">' . $taskNumber . ' - ' . $taskName . '</a>';
    }

    private function cliWrite(string $message, string $color = 'white'): void
    {
        if (is_cli()) {
            CLI::write($message, $color);
        }
    }
}
