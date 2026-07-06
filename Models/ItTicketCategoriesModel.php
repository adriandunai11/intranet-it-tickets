<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Entities\ItTicketCategoriesEntity;


class ItTicketCategoriesModel extends BaseModel
{
    protected $table = 'it_ticket_categories';
    protected $primaryKey = 'id';
    protected $returnType = ItTicketCategoriesEntity::class;
    protected $allowedFields = ['name', 'status', 'created_by', 'created_at'];


    public function forArea(int $areaId, ?string $q = null): array
    {
        $builder = $this->select('it_ticket_categories.id, it_ticket_categories.name')
            ->join('it_tickets_area_category ac', 'ac.category_id = it_ticket_categories.id')
            ->where('ac.area_id', $areaId)
            ->where('ac.status', 'active')
            ->where('it_ticket_categories.status', 'active')
            ->orderBy('ac.sort_order', 'asc')
            ->orderBy('it_ticket_categories.name', 'asc');

        if (!empty($q)) {
            $builder->like('it_ticket_categories.name', $q);
        }

        return $builder->findAll();
    }
}