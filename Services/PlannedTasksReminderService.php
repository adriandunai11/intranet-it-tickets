<?php

namespace App\Modules\ItTickets\Services;

use App\Models\BasicdataModel;
use App\Models\UserModel;
use App\Modules\ItTickets\Models\ItTicketsModel;
use CodeIgniter\CLI\CLI;

class PlannedTasksReminderService
{
    private const FROM_EMAIL = 'intranet@miellgroup.com';
    private const FROM_NAME = 'MIELL Group - Intranet';
    private const TEMPLATE_CODE = 'it_tickets_reminder_planned_tasks';
    private const STATUS = 'planned';
    private const OLDER_THAN = '-1 day';
    private const TICKET_URL = 'https://intranet.miellgroup.com/it_tickets/view/';

    private ItTicketsModel $ticketsModel;
    private BasicdataModel $basicdataModel;
    private UserModel $userModel;
    private TicketEmailService $ticketEmailService;

    public function __construct(
        ?ItTicketsModel $ticketsModel = null,
        ?BasicdataModel $basicdataModel = null,
        ?UserModel $userModel = null,
        ?TicketEmailService $ticketEmailService = null
    ) {
        $this->ticketsModel = $ticketsModel ?? new ItTicketsModel();
        $this->basicdataModel = $basicdataModel ?? new BasicdataModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->ticketEmailService = $ticketEmailService ?? new TicketEmailService();
    }

    public function send(): bool
    {
        $plannedTickets = $this->ticketsModel->getPlannedTasks(self::STATUS, self::OLDER_THAN);
        if (empty($plannedTickets)) {
            $this->cliWrite('Planned tickets: 0', 'yellow');
            return true;
        }

        $byArea = [];
        foreach ($plannedTickets as $row) {
            $areaId = (int) ($row['area'] ?? 0);
            if ($areaId > 0) {
                $byArea[$areaId][] = $row;
            }
        }

        $this->cliWrite('Planned tickets (grouped areas): ' . count($byArea), 'green');

        foreach ($byArea as $areaId => $tickets) {
            $responsibleId = (int) ($this->basicdataModel->getRowById($areaId, 'responsible') ?? 0);
            if ($responsibleId <= 0) {
                $this->cliWrite("Area #{$areaId}: nincs felelős beállítva.", 'red');
                continue;
            }

            $toEmail = $this->userModel->getRowById($responsibleId, 'email');
            if (!is_string($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $this->cliWrite("Area #{$areaId}: a felelősnek nincs érvényes e-mail címe.", 'red');
                continue;
            }

            $areaName = (string) ($this->basicdataModel->getRowById($areaId, 'name') ?? '');
            $subject = 'MIELL - ki nem osztott munkalapok: ' . ($areaName !== '' ? $areaName : ('Terület #' . $areaId));

            $sent = $this->ticketEmailService->sendTemplate(
                $toEmail,
                null,
                $subject,
                self::TEMPLATE_CODE,
                [
                    'list' => $this->buildTicketListHtml($tickets),
                    'area' => $areaName,
                ],
                self::FROM_EMAIL,
                self::FROM_NAME
            );

            $this->cliWrite("Area #{$areaId} ({$areaName}) -> {$toEmail}", $sent ? 'green' : 'red');
        }

        return true;
    }

    private function buildTicketListHtml(array $tickets): string
    {
        $listHtml = '<ul>';

        foreach ($tickets as $ticket) {
            $ticketId = (int) ($ticket['id'] ?? 0);
            $taskNumber = esc($ticket['task_number'] ?? '');
            $taskName = esc($ticket['name'] ?? '');

            $listHtml .= '<li><a href="' . self::TICKET_URL . $ticketId . '">'
                . $taskNumber . ' - ' . $taskName
                . '</a></li>';
        }

        return $listHtml . '</ul>';
    }

    private function cliWrite(string $message, string $color = 'white'): void
    {
        if (is_cli()) {
            CLI::write($message, $color);
        }
    }
}
