<?php

namespace App\Models;

use App\Models\BaseModel;

class ItTicketCategoryArea extends BaseModel
{
    protected $table      = 'it_tickets_category_area';
    protected $primaryKey = 'id';
    protected $returnType     = 'object';
    protected $allowedFields = ['area_id', 'category_id', 'sort_order', 'is_default', 'status'];

    
}