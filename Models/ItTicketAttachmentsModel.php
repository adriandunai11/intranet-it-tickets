<?php

namespace App\Modules\ItTickets\Models;

use App\Models\BaseModel;

class ItTicketAttachmentsModel extends BaseModel
{
    protected $table      = 'it_ticket_attachments';
    protected $primaryKey = 'id';
    protected $returnType     = 'object';
    protected $allowedFields = ['ticket_id', 'path', 'filename', 'created', 'uploader'];
}