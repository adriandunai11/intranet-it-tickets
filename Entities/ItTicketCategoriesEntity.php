<?php
namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Models\BasicdataModel;

class ItTicketCategoriesEntity extends Entity
{
    protected $dates = ['created_at', 'updated_at'];

    public function getAreaName(): ?string
    {
        if (empty($this->attributes['area_id'])) {
            return null;
        }

        $basicdataModel = new BasicdataModel();
        $area = $basicdataModel->find($this->attributes['area_id']);

        return $area?->name ?? null;
    }

    public function getFullName(): string
    {
        return $this->attributes['name']
            . ($this->getAreaName() ? ' (' . $this->getAreaName() . ')' : '');
    }
}