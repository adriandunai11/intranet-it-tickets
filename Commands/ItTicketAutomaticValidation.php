<?php

namespace App\Modules\ItTickets\Commands;

use App\Modules\ItTickets\Services\AutomaticValidationService;
use CodeIgniter\CLI\BaseCommand;

class ItTicketAutomaticValidation extends BaseCommand
{
    protected $group = 'IT tickets';
    protected $name = 'it_tickets:validate';
    protected $description = 'Automatic validate IT tickets';

    protected $options = [];

    public function run(array $params): void
    {
        (new AutomaticValidationService())->run();
    }
}
