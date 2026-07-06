# IT Tickets modul

Ez a mappa a CodeIgniter 4-es IT ticket modul új, rendezett belépési pontja.

## Javasolt hely az alkalmazásban

```txt
app/Modules/ItTickets/
├── Commands/
├── Config/
├── Controllers/
├── Services/
├── Models/
└── Views/
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

A controller ne tartalmazzon üzleti logikát. A controller csak:

- jogosultságot ellenőriz,
- request adatot olvas,
- service-t hív,
- response-t vagy view-t ad vissza.

A command se hívjon controllert. A command csak service-t hívjon.

Jó példa:

```php
(new ExpiredTicketsNotificationService())->send();
```

Rossz irány:

```php
It_tickets::expiredTicketsNotification();
```

## Jelenlegi állapot

Az `ExpiredTicketsNotification` command már modul alá került:

```txt
ItTickets/Commands/ExpiredTicketsNotification.php
ItTickets/Services/ExpiredTicketsNotificationService.php
```

A régi `Commands/ExpiredTicketsNotification.php` csak kompatibilitási wrapper, hogy a meglévő `php spark it_tickets:expired_tickets` hívás ne törjön el.

A `ItTickets/Controllers/ItTicketsController.php` jelenleg egy átmeneti bridge a régi `App\\Controllers\\It_tickets` controllerre. A következő lépés a nagy controller szétbontása kisebb controller/service osztályokra.
