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

    public function renderTemplate(string $templateCode, array $data): string
    {
        $template = $this->emailTemplateModel->getByWhere([
            'code' => $templateCode,
        ]);

        return \Config\Services::parser()
            ->setData($data)
            ->renderString($template[0]->data ?? '');
    }

    public function sendTemplate($to, $cc, string $subject, string $templateCode, array $data, ?string $fromEmail = null, ?string $fromName = null): bool
    {
        $html = $this->renderTemplate($templateCode, $data);

        return $this->send($to, $cc, $subject, $html, $fromEmail, $fromName);
    }

    public function sendStatusChangeEmail(object $ticket, string $ccEmails, string $statusText, string $subjectSuffix, string $templateCode): bool
    {
        $emailData = getEmailShortCodes();
        $emailData['name'] = $this->userModel->getRowById($ticket->sender_id, 'name');
        $emailData['task_number'] = '<a href="' . self::TICKET_URL . $ticket->id . '">' . $ticket->task_number . '</a> (' . $ticket->name . ')';
        $emailData['link'] = self::TICKET_URL . $ticket->id;
        $emailData['status'] = $statusText;

        return $this->sendTemplate(
            $ticket->email,
            $ccEmails,
            'MIELL munkalap: ' . $ticket->task_number . ' - ' . $subjectSuffix,
            $templateCode,
            $emailData
        );
    }

    public function send($to, $cc, string $subject, string $html, ?string $fromEmail = null, ?string $fromName = null): bool
    {
        $fromEmail = $fromEmail ?: setting('company_email');
        $fromName = $fromName ?: setting('company_name');

        $email = \Config\Services::email();
        $email->clear();
        $email->setFrom($fromEmail, $fromName);
        $email->setTo($to);

        if (!empty($cc)) {
            $email->setCC($cc);
        }

        $email->setSubject($subject);
        $email->setMessage($html);

        $sent = $email->send();

        $this->emailLogsModel->add(
            $fromEmail,
            $this->formatRecipients($to, $cc),
            $subject,
            strip_tags($html),
            $sent ? 1 : 0
        );

        return $sent;
    }

    private function formatRecipients($to, $cc): string
    {
        $toList = is_array($to) ? implode(',', $to) : (string) $to;

        if (empty($cc)) {
            return $toList;
        }

        $ccList = is_array($cc) ? implode(',', $cc) : (string) $cc;

        return $toList . ',' . $ccList;
    }
}
