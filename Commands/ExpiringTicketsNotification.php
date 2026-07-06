<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\CLIRequest;


use App\Controllers\AdminBaseController;
use App\Controllers\It_tickets;



class ExpiringTicketsNotification extends BaseCommand
{
    protected $group       = 'IT tickets';
    protected $name        = 'it_tickets:expiring_tickets';
    protected $description = 'Send notification email with expiring tickets';


    public function run(array $params)
    {
        $request = service('CLIRequest');
        It_tickets::expiringTicketsNotification();
    }
}