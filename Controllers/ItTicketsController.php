<?php

namespace App\Modules\ItTickets\Controllers;

use App\Modules\ItTickets\Services\RecurringTicketService;

class ItTicketsController extends \App\Controllers\It_tickets
{
    public function runRecurringTaskNow($id)
    {
        $this->permissionCheck('view_recurring_it_tickets');
        postAllowed();

        $result = (new RecurringTicketService())->generateOne((int) $id);

        return $this->response->setJSON([
            'status' => $result['status'],
            'message' => $result['message'],
        ]);
    }

    public static function generateRecurringTasks(): bool
    {
        return (new RecurringTicketService())->generateDueTasks();
    }

    public function testRecurringTasks()
    {
        $this->permissionCheck('manage_it_tickets');

        (new RecurringTicketService())->generateDueTasks();

        return redirect()->to('it_tickets')->with('sSuccess', 'Ismétlődő feladatok generálása lefutott.');
    }
}
