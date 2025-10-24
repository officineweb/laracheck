<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class ExceptionsBatchNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $exceptions
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
            $channels[] = \App\Notifications\Discord\DiscordChannel::class;
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $count = $this->exceptions->count();
        $siteName = $notifiable->name;

        $message = (new MailMessage)
            ->error()
            ->subject("⚠️ {$count} New Exception" . ($count > 1 ? 's' : '') . " on {$siteName}")
            ->greeting("Alert: {$count} New Exception" . ($count > 1 ? 's' : '') . ' Detected!')
            ->line("The following exceptions occurred on **{$siteName}** in the last 5 minutes:");

        foreach ($this->exceptions->take(10) as $exception) {
            $message->line('---')
                ->line('**' . ($exception->code ?? 500) . '** - ' . \Illuminate\Support\Str::limit($exception->exception ?? $exception->error, 100))
                ->line("📁 `{$exception->file}:{$exception->line}`")
                ->line('🔗 ' . $exception->full_url);
        }

        if ($count > 10) {
            $message->line('... and ' . ($count - 10) . ' more exceptions.');
        }

        $message->action('View All Exceptions', url('/exceptions?filter[site_id]=' . $notifiable->id))
            ->line('This is an automated notification from Laracheck.');

        return $message;
    }

    public function toSlack($notifiable): SlackMessage
    {
        $count = $this->exceptions->count();
        $siteName = $notifiable->name;

        $message = (new SlackMessage)
            ->error()
            ->content("⚠️ **{$count} New Exception" . ($count > 1 ? 's' : '') . " on {$siteName}**")
            ->attachment(function ($attachment) use ($count, $siteName) {
                $attachment->title("Alert: {$count} Exception" . ($count > 1 ? 's' : '') . ' Detected', url('/exceptions'))
                    ->fields([
                        'Site' => $siteName,
                        'Period' => 'Last 5 minutes',
                        'Total' => $count,
                    ]);
            });

        foreach ($this->exceptions->take(5) as $exception) {
            $message->attachment(function ($attachment) use ($exception) {
                $attachment->content(\Illuminate\Support\Str::limit($exception->exception ?? $exception->error, 150))
                    ->color($exception->code >= 500 ? 'danger' : 'warning')
                    ->fields([
                        'Code' => $exception->code ?? 500,
                        'File' => "{$exception->file}:{$exception->line}",
                        'URL' => \Illuminate\Support\Str::limit($exception->full_url, 50),
                    ]);
            });
        }

        if ($count > 5) {
            $message->attachment(function ($attachment) use ($count) {
                $attachment->content('... and ' . ($count - 5) . ' more exceptions.');
            });
        }

        return $message;
    }

    public function toDiscord($notifiable): array
    {
        $count = $this->exceptions->count();
        $siteName = $notifiable->name;

        $embeds = [[
            'title' => "⚠️ {$count} New Exception" . ($count > 1 ? 's' : '') . ' Detected',
            'description' => "The following exceptions occurred on **{$siteName}** in the last 5 minutes.",
            'color' => 15158332, // Red
            'fields' => [
                [
                    'name' => 'Site',
                    'value' => $siteName,
                    'inline' => true,
                ],
                [
                    'name' => 'Total Exceptions',
                    'value' => (string) $count,
                    'inline' => true,
                ],
                [
                    'name' => 'Period',
                    'value' => 'Last 5 minutes',
                    'inline' => true,
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ]];

        foreach ($this->exceptions->take(5) as $exception) {
            $embeds[] = [
                'color' => $exception->code >= 500 ? 15158332 : 16776960, // Red or Yellow
                'fields' => [
                    [
                        'name' => 'Code',
                        'value' => (string) ($exception->code ?? 500),
                        'inline' => true,
                    ],
                    [
                        'name' => 'Message',
                        'value' => \Illuminate\Support\Str::limit($exception->exception ?? $exception->error, 200),
                        'inline' => false,
                    ],
                    [
                        'name' => 'File',
                        'value' => "`{$exception->file}:{$exception->line}`",
                        'inline' => false,
                    ],
                ],
            ];
        }

        if ($count > 5) {
            $embeds[] = [
                'description' => '... and ' . ($count - 5) . ' more exceptions.',
                'color' => 7506394, // Gray
            ];
        }

        return [
            'content' => "@here **{$count} New Exception" . ($count > 1 ? 's' : '') . " on {$siteName}**",
            'embeds' => $embeds,
        ];
    }
}
