<?php

namespace App\Modules\ItTickets\Models;

use CodeIgniter\Model;

class ItTicketRecurringTaskRunsModel extends Model
{
    protected $table = 'it_ticket_recurring_task_runs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'recurring_task_id',
        'run_key',
        'generated_ticket_id',
        'generated_at',
    ];

    protected $useTimestamps = false;

    public function alreadyGenerated(int $recurringTaskId, string $runKey): bool
    {
        return $this->where('recurring_task_id', $recurringTaskId)
            ->where('run_key', $runKey)
            ->countAllResults() > 0;
    }
}