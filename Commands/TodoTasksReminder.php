<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\CLIRequest;


use App\Controllers\AdminBaseController;
use App\Controllers\It_tickets;



class TodoTasksReminder extends BaseCommand
{
    protected $group       = 'IT tickets';
    protected $name        = 'it_tickets:todotasks_reminder';
    protected $description = 'Send reminder email with todo tasks';


    public function run(array $params)
    {
        $request = service('CLIRequest');
        It_tickets::todoTasksReminder();
    }
}