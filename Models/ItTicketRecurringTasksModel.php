<?php

namespace App\Modules\ItTickets\Models;

use CodeIgniter\Model;

class ItTicketRecurringTasksModel extends Model
{
    protected $table = 'it_ticket_recurring_tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'name_template',
        'description_template',
        'area',
        'category',
        'participants',
        'start_date',
        'end_date',
        'frequency',
        'day_of_month',
        'is_active',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    public function getTasksForDate(string $date): array
    {
        $day = (int) date('j', strtotime($date));
        $dayOfWeek = (int) date('N', strtotime($date));
        $lastDayOfMonth = (int) date('t', strtotime($date));

        $tasks = $this->where('is_active', 1)
            ->where('start_date <=', $date)
            ->groupStart()
            ->where('end_date IS NULL', null, false)
            ->orWhere('end_date >=', $date)
            ->groupEnd()
            ->findAll();

        return array_filter($tasks, function ($task) use ($day, $dayOfWeek, $lastDayOfMonth) {
            switch ($task->frequency) {
                case 'daily':
                    return true;

                case 'weekly':
                    return (int) $task->day_of_week === $dayOfWeek;

                case 'monthly':
                    $taskDay = (int) $task->day_of_month;

                    if ($taskDay === $day) {
                        return true;
                    }

                    if ($taskDay > $lastDayOfMonth && $day === $lastDayOfMonth) {
                        return true;
                    }

                    return false;

                default:
                    return false;
            }
        });
    }
}