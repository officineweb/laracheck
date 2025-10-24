<?php

namespace App\Notifications;

use App\Models\Outage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class OutageDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(public Outage $outage) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->receive_email && $notifiable->email_outage) {
            $channels[] = 'mail';
        }

        if ($notifiable->slack_webhook) {
            $channels[] = 'slack';
        }

        if ($notifiable->discord_webhook) {
            $channels[] = DiscordChannel::class;
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $siteName = $this->outage->site->name;

        return (new MailMessage)
            ->error()
            ->subject('ALERT: Site "' . $siteName . '" is DOWN!')
            ->line('Your site **' . $siteName . '** is currently down.')
            ->line('**Occurred at (UTC TIME):** ' . $this->outage->occurred_at->setTimezone('UTC')->format('Y-m-d H:i:s'))
            ->line('**URL:** ' . $this->outage->site->url)
            ->when($this->outage->response_code, fn($mail) => $mail->line('**Response Code:** ' . $this->outage->response_code))
            ->when($this->outage->error_message, fn($mail) => $mail->line('**Error:** ' . $this->outage->error_message))
            ->action('View Outages', url('/admin/outages'));
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('🚨 Site Down: ' . $notifiable->name)
            ->attachment(function ($attachment) use ($notifiable) {
                $attachment->title('Site Outage Detected')
                    ->fields([
                        'Site' => $notifiable->name,
                        'URL' => $notifiable->url,
                        'Occurred at (UTC TIME)' => $this->outage->occurred_at->setTimezone('UTC')->format('Y-m-d H:i:s'),
                        'Response Code' => $this->outage->response_code ?? 'N/A',
                    ])
                    ->color('danger');
            });
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        return DiscordMessage::create()
            ->embed([
                'title' => '🚨 Site Down: ' . $notifiable->name,
                'description' => 'Your site is currently experiencing an outage.',
                'color' => 15158332, // Red
                'fields' => [
                    ['name' => 'Site', 'value' => $notifiable->name, 'inline' => true],
                    ['name' => 'URL', 'value' => $notifiable->url, 'inline' => true],
                    ['name' => 'Occurred at (UTC TIME)', 'value' => $this->outage->occurred_at->setTimezone('UTC')->format('Y-m-d H:i:s'), 'inline' => false],
                    ['name' => 'Response Code', 'value' => (string) ($this->outage->response_code ?? 'N/A'), 'inline' => true],
                ],
                'timestamp' => $this->outage->occurred_at->toIso8601String(),
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Site Down: ' . $notifiable->name,
            'body' => 'Your site is currently down since ' . $this->outage->occurred_at->diffForHumans() . '.',
            'site_id' => $notifiable->id,
            'outage_id' => $this->outage->id,
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => 'danger',
            'actions' => [
                [
                    'name' => 'View Outage',
                    'url' => url('/outages?tableFilters[in_outage][isActive]=true'),
                ],
            ],
        ];
    }
}
