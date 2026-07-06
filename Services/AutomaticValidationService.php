<?php

namespace App\Modules\ItTickets\Services;

use App\Models\BasicdataModel;
use App\Models\ItTicketsModel;
use App\Models\UserModel;
use CodeIgniter\CLI\CLI;

class AutomaticValidationService
{
    private const FROM_EMAIL = 'intranet@miellgroup.com';
    private const FROM_NAME = 'Miell Intranet';
    private const TEMPLATE_CODE = 'it_ticket_automatic validate';
    private const STATUS = 'finished';
    private const OLDER_THAN = '-14 days';
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

    public function run(): bool
    {
        $tickets = $this->ticketsModel->getBatch(self::STATUS, self::OLDER_THAN);

        if (count($tickets) < 1) {
            return true;
        }

        $totalSteps = count($tickets);
        $currentStep = 0;
        $success = true;

        $this->cliWrite('IT tickets to validate: ' . $totalSteps, 'green');

        foreach ($tickets as $ticket) {
            $recipients = $this->getRecipients($ticket);
            $subject = 'Automatikus validálás: ' . $ticket['task_number'] . ' munkalap validáció';

            $sent = $this->ticketEmailService->sendTemplate(
                $recipients,
                null,
                $subject,
                self::TEMPLATE_CODE,
                $this->getTemplateData($ticket),
                self::FROM_EMAIL,
                self::FROM_NAME
            );

            if (!$sent) {
                $success = false;
                $this->cliWrite('Could not send email to: ' . $recipients, 'light_red');
                $this->cliNewLine();
            } else {
                $this->ticketsModel->update($ticket['id'], [
                    'is_validated' => 1,
                    'validation_date' => date('Y-m-d H:i:s'),
                ]);
            }

            if (is_cli()) {
                CLI::showProgress($currentStep++, $totalSteps);
            }
        }

        return $success;
    }

    private function getRecipients(array $ticket): string
    {
        $areaResponsibleId = $this->basicdataModel->getRowById($ticket['area'], 'responsible');
        $areaResponsibleEmail = $this->userModel->getRowById($areaResponsibleId, 'email');
        $responsibleEmail = $this->userModel->getRowById($ticket['responsible'], 'email');

        return $areaResponsibleEmail . ',' . $responsibleEmail;
    }

    private function getTemplateData(array $ticket): array
    {
        return [
            'sender' => $this->getSenderLabel((int) $ticket['sender_id']),
            'task_number' => '<a href="' . self::TICKET_URL . (int) $ticket['id'] . '">'
                . $ticket['task_number'] . ' - ' . $ticket['name'] . '</a>',
        ];
    }

    private function getSenderLabel(int $senderId): string
    {
        $name = $this->userModel->getRowById($senderId, 'name');
        $antraId = $this->userModel->getRowById($senderId, 'antraid');

        return $name . ' (' . $antraId . ')';
    }

    private function cliWrite(string $message, string $color = 'white'): void
    {
        if (is_cli()) {
            CLI::write($message, $color);
        }
    }

    private function cliNewLine(): void
    {
        if (is_cli()) {
            CLI::newLine();
        }
    }
}
