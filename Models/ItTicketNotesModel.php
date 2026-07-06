<?php

namespace App\Models;

use App\Models\BaseModel;

class ItTicketNotesModel extends BaseModel
{
    protected $table      = 'it_ticket_notes';
    protected $primaryKey = 'id';
    protected $returnType     = 'object';
    protected $allowedFields = ['ticket_id', 'note', 'created', 'creator'];
}