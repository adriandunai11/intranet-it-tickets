<?php

namespace App\Commands;

use App\Services\ExpiredTicketsNotificationService;
use CodeIgniter\CLI\BaseCommand;

class ExpiredTicketsNotification extends BaseCommand
{
    protected $group = 'IT tickets';
    protected $name = 'it_tickets:expired_tickets';
    protected $description = 'Send notification email with expired tickets';

    public function run(array $params): void
    {
        (new ExpiredTicketsNotificationService())->send();
    }
}
