<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\CLIRequest;


use App\Controllers\AdminBaseController;
use App\Controllers\It_tickets;



class ExpiredTicketsNotification extends BaseCommand
{
    protected $group       = 'IT tickets';
    protected $name        = 'it_tickets:expired_tickets';
    protected $description = 'Send notification email with expired tickets';


    public function run(array $params)
    {
        $request = service('CLIRequest');
        It_tickets::expiredTicketsNotification();
    }
}