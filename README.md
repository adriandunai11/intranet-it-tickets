# IT Tickets modul

Ez a csomag már modul-végállapothoz van rendezve: a régi `App\Controllers\It_tickets` controller kiváltható, és a route-ok a modul controllerre mutatnak.

## Bemásolás helye

```txt
app/Modules/ItTickets/
```

## Autoload

Az `app/Config/Autoload.php` fájlban legyen felvéve:

```php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'App\Modules\ItTickets' => APPPATH . 'Modules/ItTickets',
];
```

## Routes

Az app fő `app/Config/Routes.php` fájlába elég ezt behúzni:

```php
if (file_exists(APPPATH . 'Modules/ItTickets/Config/Routes.php')) {
    require APPPATH . 'Modules/ItTickets/Config/Routes.php';
}
```

A modul saját route fájlja már erre mutat:

```txt
App\Modules\ItTickets\Controllers\ItTicketsController
```

## Törölhető / kiváltható régi fájlok

Ha a modul mappa a fenti autoloaddal aktív, ezek a régi gyökér fájlok nem kellenek ugyanebből a modulból:

```txt
app/Controllers/It_tickets.php
app/Models/ItTicketsModel.php
app/Models/ItTicketNotesModel.php
app/Models/ItTicketAttachmentsModel.php
app/Models/ItTicketCategoriesModel.php
app/Models/ItTicketsCategoryArea.php
app/Models/ItTicketRecurringTasksModel.php
app/Models/ItTicketRecurringTaskRunsModel.php
app/Entities/ItTicket.php
app/Entities/ItTicketCategoriesEntity.php
```

A commandok akkor működnek modulból, ha a CI4 command discovery/autoload látja a modul namespace-t. Ha nálad cron még `app/Commands` alatti osztályokra van kötve, hagyj ott vékony wrapper commandokat, vagy állítsd át a command discovery-t.

## Controller

```txt
Controllers/ItTicketsController.php
```

A korábbi controller neve már nincs használatban:

```txt
Controllers/It_tickets.php
```

## Service-re kötött fő folyamatok

```txt
selectResponsibleAjax()       -> TicketAssignmentService
selectAreaAjax()              -> TicketAssignmentService
updateTicketStatusAjax()      -> TicketStatusService
addComment()                  -> TicketCommentService
deleteComment()               -> TicketCommentService
addAttachment()               -> TicketAttachmentService
deleteAttachment()            -> TicketAttachmentService
validateTicket()              -> TicketValidationService
validateTicketAjax()          -> TicketValidationService
send() utáni értesítések       -> TicketCreationNotificationService
plannedTasksReminder()        -> PlannedTasksReminderService
expiringTicketsNotification() -> ExpiringTicketsNotificationService
expiredTicketsNotification()  -> ExpiredTicketsNotificationService
todoTasksReminder()           -> TodoTasksReminderService
automaticValidation()         -> AutomaticValidationService
runRecurringTaskNow()         -> RecurringTicketService
generateRecurringTasks()      -> RecurringTicketService
testRecurringTasks()          -> RecurringTicketService
```

## Service-ek

```txt
Services/AutomaticValidationService.php
Services/ExpiredTicketsNotificationService.php
Services/ExpiringTicketsNotificationService.php
Services/ItTicketCreator.php
Services/PlannedTasksReminderService.php
Services/RecurringTicketService.php
Services/TicketAssignmentService.php
Services/TicketAttachmentService.php
Services/TicketCommentService.php
Services/TicketCreationNotificationService.php
Services/TicketEmailService.php
Services/TicketStatusService.php
Services/TicketValidationService.php
Services/TodoTasksReminderService.php
```

## Fontos elv

Controller: HTTP belépési pont.
Service: üzleti logika.
TicketEmailService: sablon render, küldés, email log.
