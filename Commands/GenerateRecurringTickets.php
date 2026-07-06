<?php

namespace App\Modules\ItTickets\Commands;

use App\Modules\ItTickets\Services\RecurringTicketService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerateRecurringTickets extends BaseCommand
{
    protected $group = 'IT tickets';
    protected $name = 'tickets:generate-recurring';
    protected $description = 'Ismétlődő IT ticketek generálása';

    public function run(array $params): void
    {
        CLI::write('Ismétlődő feladatok generálása indul...', 'yellow');

        try {
            (new RecurringTicketService())->generateDueTasks();
            CLI::write('Kész', 'green');
        } catch (\Throwable $e) {
            CLI::error('Hiba történt: ' . $e->getMessage());
            log_message('error', 'Recurring command failed: ' . $e->getMessage());
        }
    }
}
