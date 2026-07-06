<?php

namespace App\Commands;

use App\Models\ItTicketsModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\CLIRequest;


use App\Controllers\AdminBaseController;
use App\Controllers\It_tickets;



class ItTicketAutomaticValidation extends BaseCommand
{
    protected $group       = 'IT tickets';
    protected $name        = 'it_tickets:validate';
    protected $description = 'Automatic validate IT tickets';

    protected $options =  array(
    );
  

    public function run(array $params)
    {
        $request = service('CLIRequest');
        It_tickets::automaticValidation();
    }
}