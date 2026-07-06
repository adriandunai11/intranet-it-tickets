<?php

use App\Modules\ItTickets\Models\ItTicketsModel;
use App\Models\BasicdataModel;
use App\Models\UserAreas;

function getTicketPermissions($ticket, ?int $userId = null): array
{
    $userId = $userId ?: (int) logged('id');

    $t = is_object($ticket) ? $ticket : model(ItTicketsModel::class)->find((int) $ticket);
    if (!$t) {
        return [
            'can_view' => false,
            'can_edit' => false,
            'can_edit_area_responsible' => false,
            'can_change_status' => false,
            'can_add_comment_or_file' => false,
            'can_validate' => false,
            'can_copy' => false,
        ];
    }

    $participants = [];
    if (!empty($t->participants)) {
        $participants = is_array($t->participants) ? $t->participants : json_decode($t->participants, true);
    }

    $areaResponsible = (int) ((new BasicdataModel)->getAreaResponsible((int) $t->area) ?? 0);

    $userAreas = (new UserAreas)->where('user_id', $userId)->findColumn('area_id') ?? [];

    $hasManage = hasPermissions('manage_it_tickets');
    $hasCopy = hasPermissions('copy_it_tickets');

    $hasViewAll = hasPermissions('view_all_tickets');
    $hasViewProgrammingPlans = hasPermissions('view_programming_plans');
    $hasEditProgrammingPlans = hasPermissions('edit_programming_plans') && ((int) $t->area == 14 || (int) $t->area == 71) && $t->status == 'project';

    $isTicketResponsible = ((int) $t->responsible === $userId);
    $isTicketSender = ((int) $t->sender_id === $userId);
    $isAreaResponsible = ($areaResponsible === $userId);
    $isParticipant = in_array((string) $userId, array_map('strval', $participants), true);
    $belongsToArea = in_array((int) $t->area, array_map('intval', $userAreas), true);

    $areaVisibility = (string) ((new BasicdataModel)->getRowById((int) $t->area, 'ticket_visibility') ?? 'area_shared');
    $areaIsShared = ($areaVisibility === 'area_shared');

    $belongsToAreaGivesAccess = $areaIsShared && $belongsToArea;

    $canValidate = (int) $t->validator === $userId && $t->status === 'finished' && (int) $t->is_validated === 0;

    return [
        'can_edit' => $hasManage || $isAreaResponsible || $isTicketResponsible,
        'can_edit_responsible' => $hasManage || $isAreaResponsible || $belongsToArea,
        'can_edit_area' => $hasManage || $isAreaResponsible,
        'can_change_status' => $hasManage || $isAreaResponsible || $isTicketResponsible,
        'can_add_comment_or_file' =>
            $hasManage || $isAreaResponsible || $isTicketResponsible || $isTicketSender || $isParticipant,
        'can_view' =>
            $hasViewAll || $isAreaResponsible || $isTicketResponsible || $isTicketSender || $isParticipant || $belongsToAreaGivesAccess,
        'can_validate' => $canValidate,
        'can_view_programming_plans' => $hasViewProgrammingPlans,
        'can_edit_programming_plans' => $hasEditProgrammingPlans,
        'can_copy' => $hasCopy,
    ];
}

function toArraySafe($val): array
{
    if (is_array($val))
        return $val;

    if (is_string($val)) {
        $s = trim($val);
        if ($s === '')
            return [];
        $j = json_decode($s, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
            return $j;
        }
        if (strpos($s, ',') !== false) {
            return array_values(array_filter(array_map('trim', explode(',', $s)), 'strlen'));
        }
        return [$s];
    }

    // null, int, bool stb.
    return [];
}