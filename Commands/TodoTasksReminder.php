<?php

namespace App\Modules\ItTickets\Commands;

use App\Modules\ItTickets\Services\TodoTasksReminderService;
use CodeIgniter\CLI\BaseCommand;

class TodoTasksReminder extends BaseCommand
{
    protected $group = 'IT tickets';
    protected $name = 'it_tickets:todotasks_reminder';
    protected $description = 'Send reminder email with todo tasks';

    public function run(array $params): void
    {
        (new TodoTasksReminderService())->send();
    }
}
