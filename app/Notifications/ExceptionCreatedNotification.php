<?php

namespace App\Notifications;

use App\Models\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class ExceptionCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Exception $exception
    ) {}

    public function via($notifiable): array
    {
        $channels = [];

        if ($notifiable->receive_email) {
            $channels[] = 'mail';
        }

        if ($notifiable->slack_webhook) {
            $channels[] = 'slack';
        }

        if ($notifiable->discord_webhook) {
            $channels[] = 'webhook';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('👾 New Exception in ' . $notifiable->title)
            ->line('A new exception has been detected in your project.')
            ->line('**Exception:** ' . $this->exception->short_exception_text)
            ->line('**File:** ' . ($this->exception->file ?? 'Unknown'))
            ->line('**Line:** ' . ($this->exception->line ?? 'Unknown'))
            ->line('**Method:** ' . ($this->exception->method ?? 'Unknown'))
            ->line('**URL:** ' . ($this->exception->full_url ?? 'Unknown'))
            ->action('View Exception Details', url('/exceptions/' . $this->exception->id))
            ->line('Please investigate and resolve this issue as soon as possible.');
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('👾 New Exception Detected!')
            ->attachment(function ($attachment) use ($notifiable) {
                $attachment->title($notifiable->title)
                    ->fields([
                        'Exception' => $this->exception->short_exception_text,
                        'File' => $this->exception->file ?? 'Unknown',
                        'Line' => $this->exception->line ?? 'Unknown',
                        'Method' => $this->exception->method ?? 'Unknown',
                        'URL' => $this->exception->full_url ?? 'Unknown',
                    ])
                    ->footer('Laracheck');
            });
    }

    public function toWebhook($notifiable): array
    {
        // Discord webhook format
        return [
            'content' => '👾 **New Exception Detected!**',
            'embeds' => [
                [
                    'title' => $notifiable->title,
                    'description' => $this->exception->short_exception_text,
                    'color' => 15158332, // Red color
                    'fields' => [
                        [
                            'name' => 'File',
                            'value' => $this->exception->file ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Line',
                            'value' => $this->exception->line ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Method',
                            'value' => $this->exception->method ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'URL',
                            'value' => $this->exception->full_url ?? 'Unknown',
                            'inline' => false,
                        ],
                    ],
                    'timestamp' => $this->exception->created_at->toIso8601String(),
                    'footer' => [
                        'text' => 'Laracheck',
                    ],
                ],
            ],
        ];
    }
}
