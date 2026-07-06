<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Entities\BaseEntity;
use CodeIgniter\I18n\Time;

class ItTicket extends BaseEntity
{
    protected $dates = ['created_at'];

    public function getResponsibleName(): string
    {
        if (empty($this->attributes['responsible'])) {
            return 'Nincs megadva';
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($this->attributes['responsible']);

        return $user?->name
            ? $user->name . ' (' . $user->antraid . ')'
            : 'Ismeretlen felelős';
    }

    public function getSenderName(): string
    {
        if (empty($this->attributes['sender_id'])) {
            return 'Nincs megadva';
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($this->attributes['sender_id']);

        return $user?->name
            ? $user->name . ' (' . $user->antraid . ')'
            : 'Ismeretlen felelős';
    }

    public function getCategoryName(): string
    {
        if (empty($this->attributes['category'])) {
            return 'Nincs megadva';
        }

        $catModel = new \App\Models\ItTicketCategoriesModel();
        $cat = $catModel->find($this->attributes['category']);

        return $cat->name ?? 'Ismeretlen kategória';
    }

    public function getStatusName(): string
    {
        $map = [
            'planned' => 'tervezett',
            'todo' => 'teendő',
            'inprogress' => 'folyamatban',
            'project' => 'projekt',
            'finished' => 'befejezett',
        ];

        $status = $this->attributes['status'] ?? null;
        return $map[$status] ?? 'Ismeretlen státusz';
    }

    public function getAreaName(): string
    {
        if (empty($this->attributes['area'])) {
            return 'Nincs megadva';
        }

        $basicdataModel = new \App\Models\BasicdataModel();
        $basicdata = $basicdataModel->find($this->attributes['area']);

        return $basicdata->name ?? 'Ismeretlen kategória';
    }


    protected function getTodoCreator()
    {
        return $this->getUserById($this->attributes['todo_creator'] ?? null);
    }

    protected function getInprogressCreator()
    {
        return $this->getUserById($this->attributes['inprogress_creator'] ?? null);
    }

    protected function getProjectCreator()
    {
        return $this->getUserById($this->attributes['project_creator'] ?? null);
    }

    protected function getWaitingCreator()
    {
        return $this->getUserById($this->attributes['waiting_creator'] ?? null);
    }
    protected function getFinishedCreator()
    {
        return $this->getUserById($this->attributes['finished_creator'] ?? null);
    }
    protected function getValidatorData()
    {
        return $this->getUserById($this->attributes['validator'] ?? null);
    }

    public function startDate(): string
    {
        return (string) $this->programming_plan_start;
    }

    public function endDateExclusive(bool $dbEndInclusive = true): string
    {
        $end = (string) $this->programming_plan_end ?: (string) $this->programming_plan_start;

        if ($dbEndInclusive) {
            $t = Time::parse($end)->addDays(1);
            return $t->toDateString(); // YYYY-MM-DD
        }
        return $end;
    }
}