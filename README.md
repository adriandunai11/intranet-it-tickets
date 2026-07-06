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

Jelenleg csak egy controller fájl maradt:

```txt
Controllers/It_tickets.php
```

A korábbi bridge controller törölve lett:

```txt
Controllers/ItTicketsController.php
```

A route-ok átmenetileg közvetlenül erre az egy controllerre mutatnak:

```php
$routes->group('it_tickets', [
    'namespace' => 'App\\Controllers',
    'filter' => 'auth',
], static function (RouteCollection $routes): void {
    $routes->get('/', 'It_tickets::index');
});
```

Ez azért átmeneti, mert a nagy controller teljes modul-namespace alá mozgatásához a teljes fájlt biztonságosan kell átírni. Addig nem maradhat két, egymást öröklő controller.

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

A hozzájuk tartozó commandok már service-t hívnak:

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

## Következő bontási célok

```txt
It_tickets controller metódusainak célzott service-re kötése
Ticket view/update/validate folyamatok service-be húzása
List/datatable query és formatter szétválasztása
Models namespace-ek fokozatos modul alá rendezése
Views útvonalak modulnézetekre igazítása
```
