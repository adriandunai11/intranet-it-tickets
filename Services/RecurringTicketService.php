<?php

namespace App\Modules\ItTickets\Services;

use App\Models\ItTicketRecurringTaskRunsModel;
use App\Models\ItTicketRecurringTasksModel;
use App\Models\UserModel;

class RecurringTicketService
{
    private const DEFAULT_DEADLINE_MODIFIER = '+1 weeks';

    private ItTicketRecurringTasksModel $tasksModel;
    private ItTicketRecurringTaskRunsModel $runsModel;
    private UserModel $userModel;
    private \App\Services\ItTicketCreator $ticketCreator;

    public function __construct(
        ?ItTicketRecurringTasksModel $tasksModel = null,
        ?ItTicketRecurringTaskRunsModel $runsModel = null,
        ?UserModel $userModel = null,
        ?\App\Services\ItTicketCreator $ticketCreator = null
    ) {
        $this->tasksModel = $tasksModel ?? new ItTicketRecurringTasksModel();
        $this->runsModel = $runsModel ?? new ItTicketRecurringTaskRunsModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->ticketCreator = $ticketCreator ?? new \App\Services\ItTicketCreator();
    }

    public function generateOne(int $taskId, ?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $runKey = $date;

        $task = $this->tasksModel->find($taskId);
        if (!$task) {
            return [
                'status' => 'error',
                'message' => 'Az ismétlődő feladat nem található.',
                'ticket_id' => null,
            ];
        }

        if ($this->runsModel->alreadyGenerated((int) $task->id, $runKey)) {
            return [
                'status' => 'warning',
                'message' => 'Erre a napra ez a feladat már le lett generálva.',
                'ticket_id' => null,
            ];
        }

        try {
            $ticketId = $this->generateFromTask($task, $date, $runKey);

            return [
                'status' => 'success',
                'message' => 'A feladat sikeresen legenerálódott.',
                'ticket_id' => $ticketId,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Manual recurring task generation failed. Task ID: ' . $taskId . ' Error: ' . $e->getMessage());

            return [
                'status' => 'error',
                'message' => 'A generálás nem sikerült.',
                'ticket_id' => null,
            ];
        }
    }

    public function generateDueTasks(?string $date = null): bool
    {
        $date = $date ?: date('Y-m-d');
        $runKey = $date;
        $tasks = $this->tasksModel->getTasksForDate($date);

        if (empty($tasks)) {
            return true;
        }

        foreach ($tasks as $task) {
            if ($this->runsModel->alreadyGenerated((int) $task->id, $runKey)) {
                continue;
            }

            try {
                $this->generateFromTask($task, $date, $runKey);
            } catch (\Throwable $e) {
                log_message('error', 'Recurring ticket generation failed. Task ID: ' . $task->id . ' Error: ' . $e->getMessage());
            }
        }

        return true;
    }

    private function generateFromTask(object $task, string $date, string $runKey): int
    {
        $creatorUser = $this->userModel->getById((int) $task->created_by);
        if (!$creatorUser) {
            throw new \RuntimeException('A létrehozó felhasználó nem található.');
        }

        $ticketId = $this->ticketCreator->create([
            'sender_id' => (int) $task->created_by,
            'uploader_id' => (int) $task->created_by,
            'area' => (int) $task->area,
            'email' => $creatorUser->email ?? null,
            'phone' => $creatorUser->phone ?? null,
            'category' => (int) $task->category,
            'deadline' => date('Y-m-d', strtotime(self::DEFAULT_DEADLINE_MODIFIER)),
            'name' => $this->renderTemplate((string) $task->name_template, $date),
            'description' => $this->renderTemplate((string) ($task->description_template ?? ''), $date),
            'validator' => (int) $task->created_by,
            'created_at' => date('Y-m-d H:i:s'),
            'participants' => !empty($task->participants)
                ? json_decode($task->participants, true)
                : [],
        ], []);

        $this->runsModel->insert([
            'recurring_task_id' => (int) $task->id,
            'run_key' => $runKey,
            'generated_ticket_id' => (int) $ticketId,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $ticketId;
    }

    private function renderTemplate(string $template, string $date): string
    {
        $timestamp = strtotime($date);

        $months = [
            1 => 'január',
            2 => 'február',
            3 => 'március',
            4 => 'április',
            5 => 'május',
            6 => 'június',
            7 => 'július',
            8 => 'augusztus',
            9 => 'szeptember',
            10 => 'október',
            11 => 'november',
            12 => 'december',
        ];

        $monthNum = (int) date('n', $timestamp);
        $prevMonthNum = (int) date('n', strtotime('-1 month', $timestamp));

        return strtr($template, [
            '{year}' => date('Y', $timestamp),
            '{month}' => date('m', $timestamp),
            '{month_name}' => $months[$monthNum] ?? '',
            '{prev_month_name}' => $months[$prevMonthNum] ?? '',
        ]);
    }
}
