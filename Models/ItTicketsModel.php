<?php

namespace App\Modules\ItTickets\Models;

use App\Models\BaseModel;
use Tatter\Audits\Traits\AuditsTrait;


class ItTicketsModel extends BaseModel
{
  use AuditsTrait;
  protected $afterInsert = ['auditInsert'];

  protected $beforeUpdate = ['auditUpdate'];
  protected $afterDelete = ['auditDelete'];

  protected $table = 'it_tickets';
  protected $primaryKey = 'id';
  protected $returnType = \App\Modules\ItTickets\Entities\ItTicket::class;
  protected $allowedFields = ['task_number', 'sender_id', 'participants', 'email', 'phone', 'responsible', 'name', 'deadline', 'area', 'description', 'category', 'created_at', 'status', 'todo_date', 'todo_creator', 'project_date', 'project_creator', 'inprogress_date', 'inprogress_creator', 'waiting_date', 'waiting_creator', 'finished_date', 'finished_creator', 'company', 'is_read', 'is_validated', 'validator', 'sent_to_validation', 'validation_date', 'read_date', 'read_by', 'is_programming_plan', 'programming_plan_start', 'programming_plan_end'];


  public function getProgrammingPlansOverlapping(string $periodStart, string $periodEnd): array
  {
    return $this->where('is_programming_plan', 1)
      ->where('programming_plan_start <', $periodEnd) // start < end (exkl.)
      ->where("DATE_ADD(programming_plan_end, INTERVAL 1 DAY) >", $periodStart) // end+1d > start
      ->orderBy('programming_plan_start', 'ASC')
      ->findAll();
  }
  public function getBatch($status = 'finished', $day = ''): array
  {
    $data = $this->asArray()
      ->whereIn('status', explode(',', $status))
      ->where('date(sent_to_validation)', date('Y-m-d', strtotime($day)))
      ->where('is_validated', 0)
      ->where('validation_date', null)
      ->orderBy('created_at', 'DESC')
      ->findAll();

    return $data;
  }

  public function getPlannedTasks($status = 'planned', $day = '')
  {
    $data = $this->asArray()
      ->whereIn('status', explode(',', $status))
      ->where('date(created_at) <=', date('Y-m-d', strtotime($day)))
      ->findAll();

    return $data;
  }

  public function getTodoTasks($status = 'todo', $day = '', $responsible = '')
  {
    $data = $this->asArray()
      ->whereIn('status', explode(',', $status))
      ->where('date(created_at) <=', date('Y-m-d', strtotime($day)))
      ->findAll();

    return $data;
  }

  public function getExpiringTickets($status = 'planned,todo,inprogress,project', $daysUntilDeadline = 1)
  {
    $targetDate = date('Y-m-d', strtotime("+{$daysUntilDeadline} day"));

    $data = $this->asArray()
      ->whereIn('status', explode(',', $status))
      ->where('deadline', $targetDate)
      ->findAll();

    return $data;
  }

  public function getExpiredTickets($status = 'planned,todo,inprogress,project')
  {
    $today = date('Y-m-d');

    $data = $this->asArray()
      ->whereIn('status', explode(',', $status))
      ->where('deadline <', $today)
      ->findAll();

    return $data;
  }
}