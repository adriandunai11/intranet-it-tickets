<?php

namespace App\Modules\ItTickets\Commands;

use App\Modules\ItTickets\Services\ExpiringTicketsNotificationService;
use CodeIgniter\CLI\BaseCommand;

class ExpiringTicketsNotification extends BaseCommand
{
    protected $group = 'IT tickets';
    protected $name = 'it_tickets:expiring_tickets';
    protected $description = 'Send notification email with expiring tickets';

    public function run(array $params): void
    {
        (new ExpiringTicketsNotificationService())->send();
    }
}
