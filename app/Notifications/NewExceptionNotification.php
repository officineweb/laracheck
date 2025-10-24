<?php

namespace App\Notifications;

use App\Models\Exception;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewExceptionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Exception $exception
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return FilamentNotification::make()
            ->danger()
            ->title('New Exception Received')
            ->body("Exception in {$this->exception->site->name}: {$this->exception->short_exception_text}")
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('View Exception')
                    ->url(route('filament.admin.resources.exceptions.view', ['record' => $this->exception->id]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
