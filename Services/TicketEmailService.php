<?php

namespace App\Modules\ItTickets\Services;

use App\Models\EmailLogsModel;
use App\Models\EmailTemplateModel;
use App\Models\UserModel;

class TicketEmailService
{
    private const TICKET_URL = 'https://intranet.miellgroup.com/it_tickets/view/';

    private EmailTemplateModel $emailTemplateModel;
    private EmailLogsModel $emailLogsModel;
    private UserModel $userModel;

    public function __construct(
        ?EmailTemplateModel $emailTemplateModel = null,
        ?EmailLogsModel $emailLogsModel = null,
        ?UserModel $userModel = null
    ) {
        $this->emailTemplateModel = $emailTemplateModel ?? new EmailTemplateModel();
        $this->emailLogsModel = $emailLogsModel ?? new EmailLogsModel();
        $this->userModel = $userModel ?? new UserModel();
    }

    public function sendStatusChangeEmail(object $ticket, string $ccEmails, string $statusText, string $subjectSuffix, string $templateCode): bool
    {
        $emailData = getEmailShortCodes();
        $emailData['name'] = $this->userModel->getRowById($ticket->sender_id, 'name');
        $emailData['task_number'] = '<a href="' . self::TICKET_URL . $ticket->id . '">' . $ticket->task_number . '</a> (' . $ticket->name . ')';
        $emailData['link'] = self::TICKET_URL . $ticket->id;
        $emailData['status'] = $statusText;

        $template = $this->emailTemplateModel->getByWhere([
            'code' => $templateCode,
        ]);

        $html = \Config\Services::parser()
            ->setData($emailData)
            ->renderString($template[0]->data ?? '');

        return $this->send(
            $ticket->email,
            $ccEmails,
            'MIELL munkalap: ' . $ticket->task_number . ' - ' . $subjectSuffix,
            $html
        );
    }

    public function send($to, ?string $cc, string $subject, string $html): bool
    {
        $email = \Config\Services::email();
        $email->clear();
        $email->setFrom(setting('company_email'), setting('company_name'));
        $email->setTo($to);

        if ($cc) {
            $email->setCC($cc);
        }

        $email->setSubject($subject);
        $email->setMessage($html);

        $sent = $email->send();

        $this->emailLogsModel->add(
            setting('company_email'),
            is_array($to) ? implode(',', $to) : (string) $to,
            $subject,
            strip_tags($html),
            $sent ? 1 : 0
        );

        return $sent;
    }
}
