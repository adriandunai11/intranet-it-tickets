# IT Tickets modul

Ez a repository egy CodeIgniter 4 IT ticket modul rendezett kivágata.

## Jelenlegi szerkezet

```txt
Commands/
Config/
Controllers/
Models/
Services/
Views/
```

## Controller állapot

Jelenleg egy controller fájl maradt:

```txt
Controllers/It_tickets.php
```

A korábbi bridge controller nincs használatban / törölve lett:

```txt
Controllers/ItTicketsController.php
```

A route-ok közvetlenül erre az egy controllerre mutatnak:

```php
$routes->group('it_tickets', [
    'namespace' => 'App\\Controllers',
    'filter' => 'auth',
], static function (RouteCollection $routes): void {
    $routes->get('/', 'It_tickets::index');
});
```

## OOP rendezési elv

A controller csak HTTP-s belépési pont legyen:

- jogosultság ellenőrzése,
- request adatok olvasása,
- service hívása,
- view / redirect / JSON response visszaadása.

Az üzleti logika service osztályokba kerüljön.
Az e-mail sablonozás, küldés és e-mail logolás külön e-mail service feladata.

Commandból nem hívunk controllert. A command csak service-t hívjon.
A command mögötti service sem küldhet közvetlenül e-mailt `Config\\Services::email()` vagy saját `EmailLogsModel` használattal; erre a `TicketEmailService` való.

## Elkészült service-ek

```txt
Services/ExpiredTicketsNotificationService.php
Services/ExpiringTicketsNotificationService.php
Services/TodoTasksReminderService.php
Services/RecurringTicketService.php
Services/TicketAttachmentService.php
Services/TicketCommentService.php
Services/AutomaticValidationService.php
Services/TicketAssignmentService.php
Services/TicketStatusService.php
Services/TicketEmailService.php
```

## Controllerből service-re kötött részek

A `Controllers/It_tickets.php` már ezeket a service-eket hívja:

```txt
selectResponsibleAjax()      -> TicketAssignmentService::changeResponsible()
selectAreaAjax()             -> TicketAssignmentService::changeArea()
updateTicketStatusAjax()     -> TicketStatusService::changeStatus()
addComment()                 -> TicketCommentService::add()
deleteComment()              -> TicketCommentService::delete()
addAttachment()              -> TicketAttachmentService::uploadMultiple()
deleteAttachment()           -> TicketAttachmentService::delete()
automaticValidation()        -> AutomaticValidationService::run()
expiringTicketsNotification()-> ExpiringTicketsNotificationService::send()
expiredTicketsNotification() -> ExpiredTicketsNotificationService::send()
todoTasksReminder()          -> TodoTasksReminderService::send()
runRecurringTaskNow()        -> RecurringTicketService::generateOne()
generateRecurringTasks()     -> RecurringTicketService::generateDueTasks()
testRecurringTasks()         -> RecurringTicketService::generateDueTasks()
```

A controller privát `sendEmail()` metódusa is a közös `TicketEmailService`-en keresztül küld.

## Command állapot

A commandok már service-t hívnak:

```txt
Commands/ExpiredTicketsNotification.php
Commands/ExpiringTicketsNotification.php
Commands/TodoTasksReminder.php
Commands/GenerateRecurringTickets.php
Commands/ItTicketAutomaticValidation.php
```

A commandok mögötti e-mailes folyamatok már a közös `TicketEmailService`-en keresztül küldenek:

```txt
ExpiredTicketsNotificationService
ExpiringTicketsNotificationService
TodoTasksReminderService
AutomaticValidationService
```

## Ellenőrzés

A módosított PHP fájlok szintaktikailag ellenőrizve lettek `php -l` paranccsal.

## Következő bontási célok

```txt
Ticket view/update/validate folyamatok külön service-be húzása
List/datatable query és formatter szétválasztása
Models namespace-ek fokozatos modul alá rendezése
Views útvonalak modulnézetekre igazítása
```
