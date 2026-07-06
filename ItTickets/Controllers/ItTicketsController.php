<?php

namespace App\Modules\ItTickets\Controllers;

/**
 * Temporary module bridge for the legacy IT tickets controller.
 *
 * The existing App\Controllers\It_tickets controller is kept untouched for now,
 * so routes can already point to the module namespace while the large controller
 * is split into smaller OOP controllers/services step by step.
 */
class ItTicketsController extends \App\Controllers\It_tickets
{
}
