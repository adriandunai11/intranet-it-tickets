<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerateRecurringTickets extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'tickets:generate-recurring';
    protected $description = 'Ismétlődő IT ticketek generálása';

    public function run(array $params)
    {
        CLI::write('Ismétlődő feladatok generálása indul...', 'yellow');

        try {
            \App\Controllers\It_tickets::generateRecurringTasks();

            CLI::write('Kész', 'green');

        } catch (\Throwable $e) {
            CLI::error('Hiba történt: ' . $e->getMessage());
            log_message('error', 'Recurring command failed: ' . $e->getMessage());
        }
    }
}