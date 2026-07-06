# IT Tickets modul

Ez a repository egy CodeIgniter 4 modul tartalma. A repository gyökere úgy van felépítve, mintha ezt a mappát bemásolnád ide:

```txt
app/Modules/ItTickets/
```

## Modul szerkezet

```txt
Commands/
Config/
Controllers/
Models/
Services/
Views/
```

## Autoload

Az `app/Config/Autoload.php` fájlban legyen felvéve a modul namespace:

```php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'App\\Modules\\ItTickets' => APPPATH . 'Modules/ItTickets',
];
```

## Routes

Az `app/Config/Routes.php` végére kerüljön:

```php
if (file_exists(APPPATH . 'Modules/ItTickets/Config/Routes.php')) {
    require APPPATH . 'Modules/ItTickets/Config/Routes.php';
}
```

## OOP rendezési elv

A controller csak HTTP-s belépési pont legyen:

- jogosultság ellenőrzése,
- request adatok olvasása,
- service hívása,
- view / redirect / JSON response visszaadása.

Az üzleti logika service osztályokba kerüljön.

Commandból nem hívunk controllert. A command csak service-t hívjon.

Jó irány:

```php
(new ExpiredTicketsNotificationService())->send();
```

Rossz irány:

```php
It_tickets::expiredTicketsNotification();
```

## Jelenlegi állapot

Az első folyamatok már service osztályokba kerültek:

```txt
Services/ExpiredTicketsNotificationService.php
Services/ExpiringTicketsNotificationService.php
Services/TodoTasksReminderService.php
Services/RecurringTicketService.php
Services/TicketAttachmentService.php
```

A hozzájuk tartozó commandok már service-t hívnak:

```txt
Commands/ExpiredTicketsNotification.php
Commands/ExpiringTicketsNotification.php
Commands/TodoTasksReminder.php
Commands/GenerateRecurringTickets.php
```

A modul controller bridge már a `RecurringTicketService`-t használja az ismétlődő ticket kézi és automata generálásánál, illetve a `TicketAttachmentService`-t a feltöltés/törlés kezelésére.

## Következő bontási célok

```txt
Services/AutomaticValidationService.php
Services/TicketCommentService.php
```
