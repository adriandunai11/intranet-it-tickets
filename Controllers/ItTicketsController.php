<?php
namespace App\Modules\ItTickets\Controllers;

use App\Controllers\AdminBaseController;

use App\Modules\ItTickets\Models\ItTicketCategoriesModel;
use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Modules\ItTickets\Models\ItTicketNotesModel;
use App\Modules\ItTickets\Models\ItTicketAttachmentsModel;

use App\Models\UserAreas;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use App\Models\UserModel;
use App\Models\BasicdataModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Hermawan\DataTables\DataTable;
use App\Modules\ItTickets\Models\ItTicketRecurringTasksModel;
use App\Modules\ItTickets\Models\ItTicketRecurringTaskRunsModel;
use App\Modules\ItTickets\Services\AutomaticValidationService;
use App\Modules\ItTickets\Services\ExpiredTicketsNotificationService;
use App\Modules\ItTickets\Services\ExpiringTicketsNotificationService;
use App\Modules\ItTickets\Services\ItTicketCreator;
use App\Modules\ItTickets\Services\PlannedTasksReminderService;
use App\Modules\ItTickets\Services\RecurringTicketService;
use App\Modules\ItTickets\Services\TicketAssignmentService;
use App\Modules\ItTickets\Services\TicketAttachmentService;
use App\Modules\ItTickets\Services\TicketCommentService;
use App\Modules\ItTickets\Services\TicketCreationNotificationService;
use App\Modules\ItTickets\Services\TicketEmailService;
use App\Modules\ItTickets\Services\TicketStatusService;
use App\Modules\ItTickets\Services\TicketValidationService;
use App\Modules\ItTickets\Services\TodoTasksReminderService;

class ItTicketsController extends AdminBaseController
{
    public $title = 'Miell Group Bejelentések';
    public $menu = 'it_tickets';


    public function index()
    {
        $this->permissionCheck('view_it_tickets');
        $session = session();

        if ($this->request->getPost()) {
            $filters = $this->request->getPost();
            $filters['include_archived'] = isset($filters['include_archived']) ? 1 : 0;
            $session->set('it_ticket_filters', $filters);
            return redirect()->to(url('it_tickets'));
        }

        $filters = $session->get('it_ticket_filters') ?? [];

        $userId = logged('id');
        $areaIds = array_column((new UserAreas())->getAreasByUser($userId), 'id');

        $sharedAreaIds = [];
        if (!empty($areaIds)) {
            $sharedAreaIds = (new BasicdataModel)
                ->select('id')
                ->whereIn('id', $areaIds)
                ->where('ticket_visibility', 'area_shared')
                ->findColumn('id') ?? [];
        }

        if (hasPermissions('view_all_tickets')) {
            $sender = (new ItTicketsModel)->select('sender_id')->groupBy('sender_id')->findAll();
        } else {
            $sender = (new ItTicketsModel)
                ->select('sender_id')
                ->groupStart()
                ->whereIn('area', !empty($sharedAreaIds) ? $sharedAreaIds : [-1])
                ->orWhere('sender_id', $userId)
                ->orWhere('responsible', $userId)
                ->orWhere("JSON_CONTAINS(participants, JSON_QUOTE('$userId'))")
                ->groupEnd()
                ->groupBy('sender_id')->findAll();
        }
        $responsibles = (new ItTicketsModel)->select('responsible')->groupBy('responsible')->findAll();

        return view('admin/it_tickets/list', compact('sender', 'filters', 'responsibles'));
    }

    public function datatable()
    {
        $this->permissionCheck('view_it_tickets');

        $filters = session()->get('it_ticket_filters') ?? [];

        $userId = logged('id');
        $areaIds = array_column((new UserAreas())->getAreasByUser($userId), 'id');

        $builder = (new ItTicketsModel())->builder()
            ->select("
                it_tickets.id,
                it_tickets.task_number   AS task_number,
                it_tickets.name          AS name,
                it_tickets.description   AS description,
                it_tickets.deadline      AS deadline,
                it_tickets.status        AS status,
                it_tickets.participants  AS participants,
                it_tickets.created_at    AS created_at,

                it_tickets.todo_date,
                it_tickets.project_date,
                it_tickets.inprogress_date,
                it_tickets.finished_date,
                it_tickets.validation_date,
                it_tickets.area,
                it_tickets.category,
                it_tickets.responsible,
                it_tickets.sender_id,

                it_tickets.waiting_date,
                it_tickets.waiting_creator,
                uwait.name AS waiting_creator_name, uwait.antraid AS waiting_creator_antraid,

                a.name  AS area_name,
                c.name  AS category_name,
                ur.name AS responsible_name, ur.antraid AS responsible_antraid,
                us.name AS sender_name,      us.antraid AS sender_antraid,
                utodo.name AS todo_creator_name,       utodo.antraid AS todo_creator_antraid,
                uproj.name AS project_creator_name,    uproj.antraid AS project_creator_antraid,
                uprog.name AS inprogress_creator_name, uprog.antraid AS inprogress_creator_antraid,
                ufin.name  AS finished_creator_name,   ufin.antraid  AS finished_creator_antraid,
                uval.name  AS validator_name,          uval.antraid  AS validator_antraid
            ")
            ->join('basicdata a', 'a.id = it_tickets.area', 'left')
            ->join('it_ticket_categories c', 'c.id = it_tickets.category', 'left')
            ->join('users ur', 'ur.id = it_tickets.responsible', 'left')
            ->join('users us', 'us.id = it_tickets.sender_id', 'left')
            ->join('users utodo', 'utodo.id = it_tickets.todo_creator', 'left')
            ->join('users uproj', 'uproj.id = it_tickets.project_creator', 'left')
            ->join('users uprog', 'uprog.id = it_tickets.inprogress_creator', 'left')
            ->join('users uwait', 'uwait.id = it_tickets.waiting_creator', 'left')

            ->join('users ufin', 'ufin.id  = it_tickets.finished_creator', 'left')
            ->join('users uval', 'uval.id  = it_tickets.validator', 'left');

        if (empty($filters['include_archived'])) {
            $builder->where('DATE(it_tickets.created_at) >=', date('Y-m-d', strtotime('-3 months')));
        }

        $sharedAreaIds = [];
        if (!empty($areaIds)) {
            $sharedAreaIds = (new BasicdataModel)
                ->select('id')
                ->whereIn('id', $areaIds)
                ->where('ticket_visibility', 'area_shared')
                ->findColumn('id') ?? [];
        }

        if (!hasPermissions('view_all_tickets')) {
            $builder->groupStart();
            if (!empty($sharedAreaIds)) {
                $builder->whereIn('it_tickets.area', $sharedAreaIds);
            }

            $builder->orWhere('it_tickets.sender_id', $userId)
                ->orWhere('it_tickets.responsible', $userId)
                ->orWhere("JSON_CONTAINS(it_tickets.participants, JSON_QUOTE('$userId'))");

            $responsibleAreaIds = (new BasicdataModel())
                ->select('id')
                ->where('responsible', $userId)
                ->findColumn('id') ?? [];
            if (!empty($responsibleAreaIds)) {
                $builder->orWhereIn('it_tickets.area', $responsibleAreaIds);
            }

            $builder->groupEnd();
        }

        if (!empty($filters['status']))
            $builder->whereIn('it_tickets.status', (array) $filters['status']);
        if (!empty($filters['validation']))
            $builder->whereIn('it_tickets.is_validated', (array) $filters['validation']);
        if (!empty($filters['sender']))
            $builder->whereIn('it_tickets.sender_id', (array) $filters['sender']);
        if (!empty($filters['category']))
            $builder->whereIn('it_tickets.category', (array) $filters['category']);
        if (!empty($filters['area']))
            $builder->whereIn('it_tickets.area', (array) $filters['area']);
        if (!empty($filters['responsible']))
            $builder->whereIn('it_tickets.responsible', (array) $filters['responsible']);
        if (!empty($filters['sentdate'])) {
            $arr = explode(' - ', $filters['sentdate']);
            if (!empty($arr[0]))
                $builder->where('DATE(it_tickets.created_at) >=', $arr[0]);
            if (!empty($arr[1]))
                $builder->where('DATE(it_tickets.created_at) <=', $arr[1]);
        }


        return DataTable::of($builder)
            ->setSearchableColumns(['it_tickets.task_number', 'it_tickets.name', 'us.name', 'ur.name', 'a.name', 'c.name', 'it_tickets.description', 'it_tickets.deadline', 'it_tickets.created_at'])

            ->edit('task_number', function ($r) {
                $url = url('it_tickets/view/' . $r->id);
                return '<a href="' . $url . '" class="text-decoration-underline">' . esc($r->task_number) . '</a>';
            })

            ->edit('name', function ($r) {
                $url = url('it_tickets/view/' . $r->id);
                return '<a href="' . $url . '" class="text-decoration-underline">' . esc($r->name) . '</a>';
            })
            ->edit('area', fn($r) => $r->area ? esc($r->area_name) : '-')
            ->edit('category', fn($r) => $r->category ? esc($r->category_name) : '-')
            ->edit('responsible', fn($r) => $r->responsible ? esc($r->responsible_name) . ' (' . esc($r->responsible_antraid) . ')' : '-')
            ->edit('participants', function ($r) {
                if (!isset($r->participants)) {
                    return '-';
                }

                $raw = $r->participants;

                if (is_string($raw)) {
                    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $raw = trim($raw, " \t\n\r\0\x0B"); // szóközök
                    if (strlen($raw) > 1 && $raw[0] === '"' && substr($raw, -1) === '"') {
                        $unq = stripcslashes(substr($raw, 1, -1));
                        $try = json_decode($unq, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $raw = $try;
                        }
                    }
                }

                $ids = null;
                if (is_string($raw)) {
                    $try = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $ids = $try;
                    }
                } elseif (is_array($raw)) {
                    $ids = $raw;
                }

                if (!is_array($ids)) {
                    if (is_string($raw) && strpos($raw, ',') !== false) {
                        $ids = array_filter(array_map('trim', explode(',', $raw)), 'strlen');
                    } else {
                        return '-';
                    }
                }

                $ids = array_values(array_filter(array_map(function ($v) {
                    if (is_numeric($v))
                        return (int) $v;
                    return null;
                }, $ids), fn($v) => !is_null($v) && $v > 0));

                if (!$ids)
                    return '-';

                static $cache = [];
                $out = [];
                foreach ($ids as $id) {
                    if (!isset($cache[$id])) {
                        $u = model('App\Models\UserModel')->getById($id);
                        $cache[$id] = $u ? ($u->name . ' (' . $u->antraid . ')') : null;
                    }
                    if ($cache[$id])
                        $out[] = esc($cache[$id]);
                }

                return $out ? implode('<br>', $out) : '-';
            })
            ->edit('status', fn($r) => esc(getProgressName($r->status)))
            ->add('todo', fn($r) => $r->todo_date ? esc($r->todo_date) . '<br>' . esc($r->todo_creator_name) . ' (' . esc($r->todo_creator_antraid) . ')' : '-')
            ->add('project', fn($r) => $r->project_date ? esc($r->project_date) . '<br>' . esc($r->project_creator_name) . ' (' . esc($r->project_creator_antraid) . ')' : '-')
            ->add('inprogress', fn($r) => $r->inprogress_date ? esc($r->inprogress_date) . '<br>' . esc($r->inprogress_creator_name) . ' (' . esc($r->inprogress_creator_antraid) . ')' : '-')
            ->add(
                'waiting',
                fn($r) => $r->waiting_date
                ? esc($r->waiting_date) . '<br>' . esc($r->waiting_creator_name) . ' (' . esc($r->waiting_creator_antraid) . ')'
                : '-'
            )
            ->add('finished', fn($r) => $r->finished_date ? esc($r->finished_date) . '<br>' . esc($r->finished_creator_name) . ' (' . esc($r->finished_creator_antraid) . ')' : '-')
            ->add('validation', fn($r) => $r->validation_date ? esc($r->validation_date) . '<br>' . esc($r->validator_name) . ' (' . esc($r->validator_antraid) . ')' : '-')
            ->add('sender', fn($r) => $r->sender_id ? esc($r->sender_name) . ' (' . esc($r->sender_antraid) . ')' : '-')
            ->edit('description', fn($r) => esc(strip_tags($r->description)))
            ->add('actions', function ($r) {
                $perm = getTicketPermissions($r->id);
                $btns = [];
                if (!empty($perm['can_validate']))
                    $btns[] = '<a class="btn btn-sm btn-default btnValidate" data-id="' . $r->id . '" title="Validálás"><i class="fas fa-check-circle"></i></a>';
                if (!empty($perm['can_edit_area']))
                    $btns[] = '<a class="btn btn-sm btn-default btnSelectArea" data-id="' . $r->id . '" title="Terület kiválasztása"><i class="fas fa-sitemap"></i></a>';
                if (!empty($perm['can_edit_responsible']))
                    $btns[] = '<a class="btn btn-sm btn-default btnSelectResponsible" data-id="' . $r->id . '" title="Felelős kiválasztása"><i class="fas fa-user-hard-hat"></i></a>';
                if (!empty($perm['can_change_status']))
                    $btns[] = '<a class="btn btn-sm btn-default btnStatus" data-id="' . $r->id . '" title="Állapot módosítás"><i class="fas fa-check"></i></a>';
                if (!empty($perm['can_view']))
                    $btns[] = '<a href="' . url('it_tickets/view/' . $r->id) . '" class="btn btn-sm btn-default" title="Megnyitás"><i class="fas fa-eye"></i></a>';
                if (!empty($perm['can_copy']))
                    $btns[] = '<a class="btn btn-sm btn-default btnCopyTicket" data-id="' . $r->id . '" title="Ticket másolása"><i class="fas fa-copy"></i></a>';

                return implode(' ', $btns);
            }, 'last')

            ->toJson(true);
    }


    public function clearFilters()
    {
        session()->remove('it_ticket_filters'); // Szűrési érték eltávolítása a session-ből
        return redirect()->to('it_tickets');
    }

    public function programming_plans()
    {
        $this->permissionCheck('view_programming_plans');

        return view('admin/it_tickets/programming_plans');
    }

    public function getProgrammingPlans()
    {
        $this->permissionCheck('view_programming_plans');

        $periodStart = Time::parse($this->request->getGet('start') ?? '')->toDateString();
        $periodEnd = Time::parse($this->request->getGet('end') ?? '')->toDateString();

        $model = new ItTicketsModel();
        $tickets = $model->getProgrammingPlansOverlapping($periodStart, $periodEnd);

        $events = array_map(function ($t) {
            $color = $this->colorForResponsible($t->responsibleName ?? null);

            return [
                'id' => (string) $t->id,
                'title' => $t->name . ' - ' . $t->responsibleName,
                'start' => $t->startDate(),
                'end' => $t->endDateExclusive(true),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $this->textColorFor($color),
                'extendedProps' => ['responsible' => $t->responsible ?? null],
            ];
        }, $tickets);

        return $this->response->setContentType('application/json')->setJSON($events);
    }

    private function colorForResponsible(?string $name): string
    {
        if (!$name)
            return '#6b7280';

        $hash = crc32(mb_strtolower($name, 'UTF-8'));

        $h = $hash % 360;

        $s = 65;  // %
        $l = 45;  // %

        return $this->hslToHex($h, $s, $l);
    }

    private function hslToHex(int $h, int $s, int $l): string
    {
        $s /= 100;
        $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60.0, 2) - 1));
        $m = $l - $c / 2;

        $segment = (int) floor($h / 60);
        switch ($segment) {
            case 0:
                $r = $c;
                $g = $x;
                $b = 0;
                break;
            case 1:
                $r = $x;
                $g = $c;
                $b = 0;
                break;
            case 2:
                $r = 0;
                $g = $c;
                $b = $x;
                break;
            case 3:
                $r = 0;
                $g = $x;
                $b = $c;
                break;
            case 4:
                $r = $x;
                $g = 0;
                $b = $c;
                break;
            default:
                $r = $c;
                $g = 0;
                $b = $x;
                break;
        }

        $r = (int) round(($r + $m) * 255);
        $g = (int) round(($g + $m) * 255);
        $b = (int) round(($b + $m) * 255);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function textColorFor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $l = 0.299 * $r + 0.587 * $g + 0.114 * $b; // percepciós fényesség
        return $l > 160 ? '#111827' : '#ffffff';
    }

    public function editProgrammingPlan()
    {
        $this->permissionCheck('edit_programming_plans');

        $id = (int) $this->request->getPost('ticketId');
        $rangeRaw = (string) $this->request->getPost('programming_period');

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'Hiányzó ticket ID.',
            ]);
        }

        [$start, $end, $isPlan] = $this->normalizeDateRange($rangeRaw);

        $data = [
            'is_programming_plan' => $isPlan ? 1 : 0,
            'programming_plan_start' => $isPlan ? $start : null,
            'programming_plan_end' => $isPlan ? $end : null,
        ];

        $model = new ItTicketsModel();

        try {
            $model->db->transStart();
            $ok = $model->update($id, $data);
            $model->db->transComplete();

            if (!$ok || $model->db->transStatus() === false) {
                throw new \RuntimeException('Adatbázis hiba frissítés közben.');
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $isPlan
                    ? 'Programozási terv frissítve.'
                    : 'Programozási terv kikapcsolva.',
                'payload' => ['id' => $id, 'data' => $data],
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'editProgrammingPlan error: {msg}', ['msg' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Nem sikerült menteni. Próbáld újra vagy jelezd az IT-nak.',
            ]);
        }
    }

    private function normalizeDateRange(string $input): array
    {
        $s = trim(preg_replace('/\s+/', ' ', $input ?? ''));

        if ($s === '') {
            return [null, null, false];
        }

        $s = str_replace('.', '-', $s);

        $startStr = null;
        $endStr = null;

        if (strpos($s, ' - ') !== false) {
            [$startStr, $endStr] = array_map('trim', explode(' - ', $s, 2));
        } else {
            $startStr = $s;
            $endStr = $s;
        }

        try {
            $start = \CodeIgniter\I18n\Time::parse($startStr)->toDateString(); // YYYY-MM-DD
            $end = \CodeIgniter\I18n\Time::parse($endStr)->toDateString();
        } catch (\Throwable $e) {
            return [null, null, false];
        }

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end, true];
    }

    public function getAreas()
    {

        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $q = $this->request->getGet('q');

        $builder = model('App\Models\BasicdataModel')->getTicketableAreas();

        if ($q) {
            $builder->like('name', $q);
        }

        $areas = $builder->orderBy('name', 'asc')->findAll();

        $results = array_map(fn($row) => ['id' => $row->id, 'text' => $row->name], $areas);
        return $this->response->setJSON(['results' => $results]);
    }

    public function getUsers()
    {

        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $q = $this->request->getGet('q');

        $builder = model('App\Models\UserModel')
            ->where('whoiswho', 1)
            ->orderBy('name', 'asc');

        if ($q) {
            $builder->groupStart()
                ->like('name', $q)
                ->orLike('antraid', $q)
                ->groupEnd();
        }

        $users = $builder->findAll();

        $results = array_map(fn($row) => [
            'id' => $row->id,
            'text' => $row->name,
            'antraid' => $row->antraid
        ], $users);

        return $this->response->setJSON(['results' => $results]);
    }

    public function getCategoriesByArea()
    {

        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $areaId = (int) $this->request->getGet('areaId');
        $q = $this->request->getGet('q');

        if ($areaId <= 0) {
            return $this->response->setJSON(['results' => []]);
        }

        $categories = (new ItTicketCategoriesModel)
            ->forArea($areaId, $q);

        $results = array_map(
            fn($row) => ['id' => (int) $row->id, 'text' => $row->name],
            $categories
        );

        return $this->response->setJSON(['results' => $results]);
    }

    public function getResponsiblesByArea($departmentId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $q = $this->request->getGet('q');

        $userModel = new UserModel();
        $users = $userModel->getUsersByArea($departmentId);

        if ($q) {
            $users->groupStart()
                ->like('name', $q)
                ->orLike('antraid', $q)
                ->groupEnd();

        }

        $users = $users->orderBy('name', 'asc')->findAll();

        $results = array_map(fn($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'antraid' => $row->antraid
        ], $users);

        return $this->response->setJSON(['ok' => true, 'list' => $results]);
    }
    public function send()
    {
        $this->permissionCheck('create_it_ticket');
        postAllowed();

        if (!$this->validate([
            'task_name' => 'required',
            'area' => 'required',
            'category' => 'required',
            'files' => [
                'max_size[files,25600]',
            ],
        ])) {
            return redirect()->to('/it_tickets')
                ->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        try {
            $creator = new ItTicketCreator();
            $participants = post('participants') ?: [];

            $id = $creator->create([
                'sender_id' => logged('id'),
                'uploader_id' => logged('id'),
                'area' => post('area'),
                'email' => logged('email'),
                'phone' => logged('phone'),
                'category' => post('category'),
                'deadline' => date('Y-m-d', strtotime('+1 weeks')),
                'name' => post('task_name'),
                'description' => post('description'),
                'validator' => logged('id'),
                'created_at' => date('Y-m-d H:i:s'),
                'participants' => $participants,
            ], $this->request->getFileMultiple('files') ?: []);

            (new TicketCreationNotificationService())->notifyCreated($id, [
                'sender_name' => logged('name'),
                'sender_email' => logged('email'),
                'sender_phone' => logged('phone'),
                'task_name' => post('task_name'),
                'description' => post('description'),
                'category' => post('category'),
                'area' => post('area'),
                'participants' => $participants,
            ]);

            $ticketRow = (new ItTicketsModel())->find($id);
            $taskNumber = $ticketRow->task_number ?? '';

            model('App\Models\ActivityLogModel')->add(
                logged('name') . ' (#' . logged('id') . ') bejelentést küldött az IT ürlapon keresztül. Bejelentés azonosító: ' . $id
            );

            return redirect()->to('it_tickets')
                ->with('sSuccess', 'A bejelentésed sikeresen rögzítettük ' . $taskNumber . ' számon.');
        } catch (\Throwable $e) {
            log_message('error', 'Ticket create failed: ' . $e->getMessage());
            return redirect()->to('it_tickets')
                ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
        }
    }


    function unique_random()
    {
        $model = new ItTicketsModel();
        $year = date('Y');
        $prefix = 'IT' . date('y');

        try {
            $model->db->transStart();

            $row = $model->db
                ->query(
                    "SELECT MAX(CAST(SUBSTRING(task_number, 5) AS UNSIGNED)) AS max_num
                 FROM {$model->table}
                 WHERE YEAR(created_at) = ?
                 FOR UPDATE",
                    [$year]
                )
                ->getRow();

            $nextNum = ($row->max_num ?? 0) + 1;

            $model->db->transComplete();

            return $prefix . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        } catch (\Throwable $e) {
            $model->db->transRollback();
            throw $e;
        }
    }

    public function getTicket($id = null)
    {

        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $model = new ItTicketsModel();
        $ticket = $model->find((int) $id);

        if (!$ticket) {
            return $this->response->setJSON(['status' => false]);
        }

        $data = $ticket->toArray();
        $data['responsible_name'] = $ticket->responsibleName; // <-- getter meghívása
        $data['area_name'] = $ticket->areaName; // <-- getter meghívása

        return $this->response->setJSON([
            'status' => true,
            'data' => $data,
        ], 200, JSON_UNESCAPED_UNICODE);
    }
    public function selectResponsibleAjax()
    {
        postAllowed();

        $ticketId = (int) post('ticketId');
        $responsibleId = (int) post('responsible');
        $ticket = (new ItTicketsModel())->getById($ticketId);
        $perm = getTicketPermissions($ticket);

        if (empty($perm['can_edit_responsible'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Nincs jogosultságod felelőst módosítani ezen a jegyen.',
            ]);
        }

        $result = (new TicketAssignmentService())->changeResponsible(
            $ticketId,
            $responsibleId,
            (int) logged('id'),
            (string) logged('antraid')
        );

        return $this->response->setJSON($result);
    }
    public function selectAreaAjax()
    {
        postAllowed();

        $ticketId = (int) post('ticketId');
        $newAreaId = (int) post('edit_area');
        $ticket = (new ItTicketsModel())->getById($ticketId);
        $perm = getTicketPermissions($ticket);

        if (empty($perm['can_edit_area'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Nincs jogosultságod területet módosítani ezen a jegyen.',
            ]);
        }

        $result = (new TicketAssignmentService())->changeArea(
            $ticketId,
            $newAreaId,
            (int) logged('id'),
            (string) logged('name'),
            (string) logged('antraid')
        );

        return $this->response->setJSON($result);
    }
    public function updateTicketStatusAjax()
    {
        postAllowed();

        $ticketId = (int) post('id');
        $status = (string) post('status');
        $ticket = (new ItTicketsModel())->getById($ticketId);
        $perm = getTicketPermissions($ticket);

        if (empty($perm['can_change_status'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Nincs jogosultságod státuszt módosítani ezen a jegyen.',
            ]);
        }

        $result = (new TicketStatusService())->changeStatus(
            $ticketId,
            $status,
            (int) logged('id'),
            (string) logged('antraid')
        );

        return $this->response->setJSON($result);
    }

    public function view($id)
    {

        $perm = getTicketPermissions($id);
        $ticket = (new ItTicketsModel)->getById($id);

        if (!$perm['can_view']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }

        $notes = (new ItTicketNotesModel)->getByWhere(['ticket_id' => $id], ['order' => ['id', 'desc'],]);
        $attachments = (new ItTicketAttachmentsModel)->getByWhere(['ticket_id' => $id]);

        $participants = json_decode($ticket->participants, true);


        $participantsInfo = $this->getParticipantsNamesAndAntraid($participants);

        $this->updatePageData(['title' => $ticket->task_number . ' ' . $ticket->categoryName . ' - ' . $ticket->name . ' | IT Bejelentések']);




        return view('admin/it_tickets/view', compact('ticket', 'notes', 'attachments', 'participantsInfo'));
    }


    public function update($id)
    {
        $perm = getTicketPermissions($id);
        if (!$perm['can_edit']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }
        postAllowed();


        $data = [
            "name" => post('task_name'),
            "category" => post('category'),
            'deadline' => post('deadline'),
            "description" => post("description"),
            "participants" => json_encode(post('participants') ?: [])
        ];

        if ((new ItTicketsModel)->update($id, $data)) {
            return redirect()->to('it_tickets/view/' . $id)->with('sSuccess', 'Sikeresen módosítottad a bejelentést.');
        } else {
            return redirect()->to('it_tickets/view/' . $id)->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
        }
    }
    public function validateTicket($id)
    {
        $ticket = (new ItTicketsModel())->getById((int) $id);
        if (!$ticket) {
            return redirect()->to('it_tickets');
        }

        $perm = getTicketPermissions($ticket->id);
        postAllowed();

        if (empty($perm['can_validate'])) {
            return $this->response->redirect('/errors/denied');
        }

        $result = (new TicketValidationService())->validate(
            (int) $id,
            (int) post('status') === 1,
            post('validateComment')
        );

        return redirect()->to('it_tickets/view/' . $id)
            ->with($result['status'] ? 'sSuccess' : 'sError', $result['message']);
    }
    public function validateTicketAjax()
    {
        postAllowed();

        $id = (int) post('ticketIdValidation');
        $ticket = (new ItTicketsModel())->getById($id);
        if (!$ticket) {
            return $this->response->setJSON(['status' => false, 'data' => null]);
        }

        $perm = getTicketPermissions($ticket->id);
        if (empty($perm['can_validate'])) {
            return $this->response->setJSON(['status' => false, 'data' => null]);
        }

        $result = (new TicketValidationService())->validate(
            $id,
            (int) post('validatedText') === 1,
            post('validateComment')
        );

        return $this->response->setJSON([
            'status' => $result['status'],
            'data' => $result['data'],
        ]);
    }
    public static function plannedTasksReminder()
    {
        return (new PlannedTasksReminderService())->send();
    }
    public static function expiringTicketsNotification()
    {
        return (new ExpiringTicketsNotificationService())->send();
    }
    public static function expiredTicketsNotification()
    {
        return (new ExpiredTicketsNotificationService())->send();
    }
    public function addComment($ticket_id)
    {
        $this->permissionCheck('create_it_ticket');

        $ticketId = (int) $ticket_id;
        $ticket = (new ItTicketsModel())->getById($ticketId);
        if (!$ticket) {
            return redirect()->to('it_tickets');
        }

        $perm = getTicketPermissions($ticket);
        if (empty($perm['can_add_comment_or_file'])) {
            return $this->response->redirect('/errors/denied');
        }

        postAllowed();

        try {
            $ok = (new TicketCommentService())->add(
                $ticketId,
                (string) post('comment'),
                (int) logged('id')
            );

            if ($ok) {
                return redirect()->to('it_tickets/view/' . $ticketId)
                    ->with('sSuccess', 'Jegyzet sikeresen létrehozva.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Ticket comment create failed: ' . $e->getMessage());
        }

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with('sError', 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }
    public function deleteComment($id)
    {
        $noteId = (int) $id;
        $service = new TicketCommentService();
        $note = $service->find($noteId);

        if (!$note) {
            return redirect()->to('it_tickets');
        }

        if ((int) $note->creator !== (int) logged('id') && !(hasPermissions('manage_it_tickets') && (int) $note->creator !== 0)) {
            return $this->response->redirect('/errors/denied');
        }

        $result = $service->delete($noteId);
        $ticketId = (int) ($result['ticket_id'] ?? $note->ticket_id);

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with($result['status'] ? 'sSuccess' : 'sError', $result['status'] ? $result['message'] : 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }
    public function addAttachment($ticket_id)
    {
        $ticketId = (int) $ticket_id;
        $ticket = (new ItTicketsModel())->getById($ticketId);
        $perm = getTicketPermissions($ticket);

        if (empty($perm['can_add_comment_or_file'])) {
            return $this->response->redirect('/errors/denied');
        }

        postAllowed();

        if (!$this->validate([
            'files' => [
                'max_size[files,25600]',
            ],
        ])) {
            return redirect()->to('it_tickets/view/' . $ticketId)
                ->withInput()
                ->with('validation_add_attachment', $this->validator->getErrors());
        }

        $uploaded = (new TicketAttachmentService())->uploadMultiple(
            $ticketId,
            $this->request->getFileMultiple('files') ?: [],
            (int) logged('id')
        );

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with($uploaded ? 'sSuccess' : 'sError', $uploaded ? 'Sikeres állomány feltöltés.' : 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }
    public function deleteAttachment($id)
    {
        $attachmentId = (int) $id;
        $service = new TicketAttachmentService();
        $attachment = $service->find($attachmentId);

        if (!$attachment) {
            return redirect()->to('it_tickets');
        }

        if ((int) $attachment->uploader !== (int) logged('id') && !hasPermissions('manage_it_tickets')) {
            return $this->response->redirect('/errors/denied');
        }

        $result = $service->delete($attachmentId);
        $ticketId = (int) ($result['ticket_id'] ?? $attachment->ticket_id);

        return redirect()->to('it_tickets/view/' . $ticketId)
            ->with($result['status'] ? 'sSuccess' : 'sError', $result['status'] ? $result['message'] : 'Valami hiba történt. Kérlek, hogy jelezd az IT osztály felé.');
    }
    private function sendEmail($to, $cc = false, $subject, $html)
    {
        if ($to && $subject && $html) {
            (new TicketEmailService())->send($to, $cc ?: null, $subject, $html);
        }
    }

    private function createSystemNote($ticket_id, $note)
    {
        if ($ticket_id && $note) {

            (new ItTicketNotesModel)->create([
                'ticket_id' => $ticket_id,
                'note' => $note,
                'creator' => 0,
                'created' => new Time('now')
            ]);
        }
    }
    public static function automaticValidation()
    {
        return (new AutomaticValidationService())->run();
    }
    public static function todoTasksReminder()
    {
        return (new TodoTasksReminderService())->send();
    }

    function getParticipantsNamesAndAntraid($participants)
    {
        if (!empty($participants) && is_array($participants)) {
            $userModel = new UserModel();

            $users = $userModel->whereIn('id', $participants)->findAll();

            $result = [];
            foreach ($users as $user) {
                $result[] = [
                    'name' => $user->name,
                    'antraid' => $user->antraid
                ];
            }

            return $result;
        }

        return [];
    }

    public function exportRiport()
    {
        $this->permissionCheck('export_it_ticket_riport');

        $session = session();
        $session->get('it_ticket_filters');

        $filters = $session->get('it_ticket_filters');

        $tickets = new ItTicketsModel;

        $halfYearAgo = date('Y-m-d', strtotime('-3 months'));
        if (empty($filters['include_archived'])) {
            $tickets = $tickets->where('date(created_at) >=', $halfYearAgo);
        }

        if (!empty($filters)) {

            if (!empty($filters['status'])) {
                $tickets = $tickets->whereIn('status', $filters['status']);
            }

            if (!empty($filters['validation'])) {
                $tickets = $tickets->whereIn('is_validated', $filters['validation']);
            }

            if (!empty($filters['sentdate'])) {
                $arr = explode(" - ", $filters['sentdate'], 3);

                $tickets = $tickets->where('date(created_at) >=', $arr[0]);
                $tickets = $tickets->where('date(created_at) <=', $arr[1]);
            }


            if (!empty($filters['sender'])) {
                $tickets = $tickets->whereIn('sender_id', $filters['sender']);

            }

            if (!empty($filters['category'])) {
                $tickets = $tickets->whereIn('category', $filters['category']);

            }

            if (!empty($filters['responsible'])) {
                $tickets = $tickets->whereIn('responsible', $filters['responsible']);

            }
        }

        $tickets = $tickets->findAll();

        $categoryStats = [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Kimutatás");
        $sheet->setCellValue('A1', 'Kategória');
        $sheet->setCellValue('B1', 'Mennyiség (db)');
        $sheet->setCellValue('C1', 'Folyamatban (átlag nap)');
        $sheet->setCellValue('D1', 'Lezárva (átlag nap)');

        $allTicketsSheet = $spreadsheet->createSheet();
        $allTicketsSheet->setTitle("Összes ticket");
        $allTicketsSheet->setCellValue('A1', 'Bejelentés AZ');
        $allTicketsSheet->setCellValue('B1', 'Név');
        $allTicketsSheet->setCellValue('C1', 'Kategória');
        $allTicketsSheet->setCellValue('D1', 'Felelős');
        $allTicketsSheet->setCellValue('E1', 'Állapot');
        $allTicketsSheet->setCellValue('F1', 'Határidő');
        $allTicketsSheet->setCellValue('G1', 'Teendő');
        $allTicketsSheet->setCellValue('H1', 'Projekt');
        $allTicketsSheet->setCellValue('I1', 'Folyamatban');
        $allTicketsSheet->setCellValue('I1', 'Bejelentő  válaszára vár');
        $allTicketsSheet->setCellValue('J1', 'Befejezett');
        $allTicketsSheet->setCellValue('K1', 'Létrehozó');
        $allTicketsSheet->setCellValue('L1', 'Létrehozás dátuma');
        $allTicketsSheet->setCellValue('M1', 'Napok');
        $allTicketsSheet->setCellValue('N1', 'Lezárva');


        $rowAll = 2;
        foreach ($tickets as $t) {
            $cat = $t->categoryName ?? 'Nincs megadva';

            if (!isset($categoryStats[$cat])) {
                $categoryStats[$cat] = [
                    'count' => 0,
                    'days_sum' => 0,
                    'closed_count' => 0,
                    'closed_days_sum' => 0,
                ];
            }

            $baseDate = null;
            if (!empty($t->inprogress_date)) {
                $baseDate = $t->inprogress_date;
            } elseif (!empty($t->project_date)) {
                $baseDate = $t->project_date;
            } elseif (!empty($t->waiting_date)) {
                $baseDate = $t->waiting_date;
            } elseif (!empty($t->finished_date)) {
                $baseDate = $t->finished_date;
            }

            $days = null;
            if ($baseDate) {
                try {
                    $createdDate = new \DateTime(substr($t->created_at, 0, 10));
                    $selectedDate = new \DateTime(substr($baseDate, 0, 10));
                    $diff = $selectedDate->diff($createdDate);
                    $days = $diff->days;
                    if ($days > 0) {
                        $days = max(1, $days);
                    }
                } catch (\Exception $e) {
                    $days = 0;
                }
            }

            $closedDays = null;

            if (strtolower($t->status) === 'finished' && !empty($t->finished_date)) {
                try {
                    $categoryStats[$cat]['closed_count']++;
                    $createdDate = new \DateTime(substr($t->created_at, 0, 10));
                    $finishedDate = new \DateTime(substr($t->finished_date, 0, 10));
                    $diff = $finishedDate->diff($createdDate);

                    if ($diff->days === 0) {
                        $closedDays = 0;
                    } else {
                        $closedDays = max(1, $diff->days);
                    }
                } catch (\Exception $e) {
                    $closedDays = null;
                }
            } else {
                $closedDays = '-';
            }



            $categoryStats[$cat]['count']++;
            if ($days !== null) {
                $categoryStats[$cat]['days_sum'] += $days;
            }

            if (is_numeric($closedDays)) {
                if (!isset($categoryStats[$cat]['closed_days_sum'])) {
                    $categoryStats[$cat]['closed_days_sum'] = 0;
                }
                $categoryStats[$cat]['closed_days_sum'] += $closedDays;
            }

            $allTicketsSheet->setCellValue("A{$rowAll}", $t->task_number);
            $allTicketsSheet->setCellValue("B{$rowAll}", $t->name);
            $allTicketsSheet->setCellValue("C{$rowAll}", $t->categoryName);
            $allTicketsSheet->setCellValue("D{$rowAll}", $t->responsibleName);
            $allTicketsSheet->setCellValue("E{$rowAll}", $t->statusName);
            $allTicketsSheet->setCellValue("F{$rowAll}", $t->deadline);
            $allTicketsSheet->setCellValue("G{$rowAll}", $t->todo_date ?? '-');
            $allTicketsSheet->setCellValue("H{$rowAll}", $t->project_date ?? '-');
            $allTicketsSheet->setCellValue("I{$rowAll}", $t->inprogress_date ?? '-');
            $allTicketsSheet->setCellValue("I{$rowAll}", $t->waiting_date ?? '-');

            $allTicketsSheet->setCellValue("J{$rowAll}", $t->finished_date ?? '-');
            $allTicketsSheet->setCellValue("K{$rowAll}", $t->senderName ?? '-');
            $allTicketsSheet->setCellValue("L{$rowAll}", $t->created_at);
            $allTicketsSheet->setCellValue("M{$rowAll}", $days);
            $allTicketsSheet->setCellValue("N{$rowAll}", $closedDays);



            $rowAll++;
        }

        $row = 2;
        $totalCount = 0;
        $totalDaysSum = 0;
        $totalClosedCount = 0;
        $totalClosedDaysSum = 0;
        foreach ($categoryStats as $category => $stats) {
            $avgDays = $stats['count'] > 0 ? round($stats['days_sum'] / $stats['count'], 2) : 0;
            $avgClosedDays = $stats['closed_count'] > 0 ? round($stats['closed_days_sum'] / $stats['closed_count'], 2) : 0;

            $sheet->setCellValue("A{$row}", $category);
            $sheet->setCellValue("B{$row}", $stats['count']);

            $sheet->setCellValue("C{$row}", $avgDays);
            $sheet->setCellValue("D{$row}", $avgClosedDays);

            $totalCount += $stats['count'];
            $totalDaysSum += $stats['days_sum'];
            $totalClosedCount += $stats['closed_count'];
            $totalClosedDaysSum += $stats['closed_days_sum'];

            $row++;
        }



        $avgTotalDays = $totalCount > 0 ? round($totalDaysSum / $totalCount, 2) : 0;
        $avgTotalClosedDays = $totalClosedCount > 0 ? round($totalClosedDaysSum / $totalClosedCount, 2) : 0;

        $sheet->setCellValue("A{$row}", 'Összesen');
        $sheet->setCellValue("B{$row}", $totalCount);
        $sheet->setCellValue("C{$row}", $avgTotalDays);
        $sheet->setCellValue("D{$row}", $avgTotalClosedDays);

        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $this->formatExcel($allTicketsSheet);
        $this->formatExcel($sheet);
        $writer = new Xlsx($spreadsheet);
        $filename = 'it_ticket_riport_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;

    }

    private function formatExcel($table)
    {
        $lastColumn = $table->getHighestColumn();
        $lastRow = $table->getHighestRow();

        $table->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        $table->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FFD9D9D9'],
            ],
        ]);

        $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);

        for ($col = 1; $col <= $lastColumnIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $table->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    private function calculateDays(?string $start, ?string $end): ?int
    {
        if (empty($start) || empty($end)) {
            return null;
        }

        try {
            $startDate = new \DateTime(substr($start, 0, 10));
            $endDate = new \DateTime(substr($end, 0, 10));
            $diff = $endDate->diff($startDate);
            return $diff->days === 0 ? 0 : max(1, $diff->days);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function recurring()
    {
        $this->permissionCheck('view_recurring_it_tickets');

        return view('admin/it_tickets/recurring_list');
    }

    public function recurringDatatable()
    {
        $this->permissionCheck('view_recurring_it_tickets');

        $builder = (new ItTicketRecurringTasksModel())->builder()
            ->select("
            it_ticket_recurring_tasks.*,
            it_ticket_recurring_tasks.name_template,
            it_ticket_recurring_tasks.frequency,
            it_ticket_recurring_tasks.start_date,
            it_ticket_recurring_tasks.end_date,
            it_ticket_recurring_tasks.is_active,
            a.name AS area_name,
            c.name AS category_name,
            u.name AS creator_name
        ")
            ->join('basicdata a', 'a.id = it_ticket_recurring_tasks.area', 'left')
            ->join('it_ticket_categories c', 'c.id = it_ticket_recurring_tasks.category', 'left')
            ->join('users u', 'u.id = it_ticket_recurring_tasks.created_by', 'left');

        return DataTable::of($builder)
            ->edit('frequency', function ($row) {
                return match ($row->frequency) {
                    'daily' => 'Napi',
                    'weekly' => 'Heti',
                    'monthly' => 'Havi',
                    default => $row->frequency
                };
            })
            ->add('schedule_value', function ($row) {
                if ($row->frequency === 'daily') {
                    return '-';
                }

                if ($row->frequency === 'weekly') {
                    $days = [
                        1 => 'Hétfő',
                        2 => 'Kedd',
                        3 => 'Szerda',
                        4 => 'Csütörtök',
                        5 => 'Péntek',
                        6 => 'Szombat',
                        7 => 'Vasárnap',
                    ];

                    return $days[(int) $row->day_of_week] ?? '-';
                }

                if ($row->frequency === 'monthly') {
                    return (int) $row->day_of_month . '. nap';
                }

                return '-';
            })
            ->edit('is_active', function ($row) {
                return (int) $row->is_active === 1
                    ? '<span class="badge badge-success">Aktív</span>'
                    : '<span class="badge badge-secondary">Inaktív</span>';
            })
            ->add('last_run', function ($row) {
                $lastRun = (new ItTicketRecurringTaskRunsModel())
                    ->where('recurring_task_id', $row->id)
                    ->orderBy('generated_at', 'DESC')
                    ->first();

                return $lastRun ? esc($lastRun->generated_at) : '-';
            })
            ->add('actions', function ($row) {
                $buttons = [];

                if (hasPermissions('manage_it_tickets')) {
                    $buttons[] = '<a class="btn btn-sm btn-default btnEditRecurring" data-id="' . $row->id . '" title="Szerkesztés"><i class="fas fa-pen"></i></a>';
                    $buttons[] = '<a class="btn btn-sm btn-default btnDeleteRecurring" data-id="' . $row->id . '" title="Törlés"><i class="fas fa-trash"></i></a>';
                    $buttons[] = '<a class="btn btn-sm btn-default btnRunRecurring" data-id="' . $row->id . '" title="Futtatás most"><i class="fas fa-play"></i></a>';
                }

                return implode(' ', $buttons);
            }, 'last')
            ->toJson(true);
    }

    public function getRecurringTask($id = null)
    {
        $this->permissionCheck('view_it_tickets');

        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $task = (new ItTicketRecurringTasksModel())->find((int) $id);

        if (!$task) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Az ismétlődő feladat nem található.'
            ]);
        }

        $data = is_object($task) && method_exists($task, 'toArray')
            ? $task->toArray()
            : (array) $task;

        $data['participants'] = !empty($data['participants'])
            ? json_decode($data['participants'], true)
            : [];

        $data['area_name'] = model('App\Models\BasicdataModel')->getRowById($data['area'], 'name');
        $data['category_name'] = model('App\Modules\ItTickets\Models\ItTicketCategoriesModel')->getRowById($data['category'], 'name');

        return $this->response->setJSON([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function saveRecurringTask()
    {
        $this->permissionCheck('create_recurring_it_tickets');
        postAllowed();

        $id = (int) post('id');
        $frequency = post('frequency');

        $rules = [
            'name_template' => 'required',
            'area' => 'required|integer',
            'category' => 'required|integer',
            'frequency' => 'required|in_list[daily,weekly,monthly]',
            'start_date' => 'required',
        ];

        if ($frequency === 'weekly') {
            $rules['day_of_week'] = 'required|integer';
        }

        if ($frequency === 'monthly') {
            $rules['day_of_month'] = 'required|integer';
        }

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Validációs hiba.',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        $participants = post('participants') ?: [];

        $data = [
            'name_template' => post('name_template'),
            'description_template' => post('description_template'),
            'area' => (int) post('area'),
            'category' => (int) post('category'),
            'participants' => json_encode($participants),
            'start_date' => post('start_date'),
            'end_date' => post('end_date') ?: null,
            'frequency' => $frequency,
            'day_of_week' => $frequency === 'weekly' ? (int) post('day_of_week') : null,
            'day_of_month' => $frequency === 'monthly' ? (int) post('day_of_month') : null,
            'is_active' => post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $model = new ItTicketRecurringTasksModel();

        if ($id > 0) {
            $ok = $model->update($id, $data);

            return $this->response->setJSON([
                'status' => $ok ? 'success' : 'error',
                'message' => $ok
                    ? 'Az ismétlődő feladat sikeresen módosítva.'
                    : 'A módosítás nem sikerült.',
            ]);
        }

        $data['created_by'] = logged('id');
        $data['created_at'] = date('Y-m-d H:i:s');

        $newId = $model->insert($data, true);

        return $this->response->setJSON([
            'status' => $newId ? 'success' : 'error',
            'message' => $newId
                ? 'Az ismétlődő feladat sikeresen létrehozva.'
                : 'A létrehozás nem sikerült.',
        ]);
    }

    public function deleteRecurringTask($id)
    {
        $this->permissionCheck('delete_recurring_it_tickets');
        postAllowed();

        $model = new ItTicketRecurringTasksModel();
        $task = $model->find((int) $id);

        if (!$task) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Az ismétlődő feladat nem található.',
            ]);
        }

        $ok = $model->delete((int) $id);

        return $this->response->setJSON([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok
                ? 'Az ismétlődő feladat törölve lett.'
                : 'A törlés nem sikerült.',
        ]);
    }
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
    public static function generateRecurringTasks()
    {
        return (new RecurringTicketService())->generateDueTasks();
    }
    private static function renderRecurringTemplate(string $template, string $date): string
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

        $replacements = [
            '{year}' => date('Y', $timestamp),
            '{month}' => date('m', $timestamp),
            '{month_name}' => $months[$monthNum] ?? '',
            '{prev_month_name}' => $months[$prevMonthNum] ?? '',
        ];

        return strtr($template, $replacements);
    }
    public function testRecurringTasks()
    {
        $this->permissionCheck('manage_it_tickets');

        (new RecurringTicketService())->generateDueTasks();

        return redirect()->to('it_tickets')->with('sSuccess', 'Ismétlődő feladatok generálása lefutott.');
    }
}

