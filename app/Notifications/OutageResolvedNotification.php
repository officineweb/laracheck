<?php

namespace App\Notifications;

use App\Models\Outage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class OutageResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(public Outage $outage) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->receive_email && $notifiable->email_resolved) {
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
        $duration = $this->outage->occurred_at->diff($this->outage->resolved_at);
        $siteName = $this->outage->site->name;

        return (new MailMessage)
            ->success()
            ->subject('Site "' . $siteName . '" is UP!')
            ->line('Great news! Your site **' . $siteName . '** is back online.')
            ->line('**Downtime Duration:** ' . $duration->format('%H:%I:%S'))
            ->line('**Occurred at (UTC TIME):** ' . $this->outage->occurred_at->setTimezone('UTC')->format('Y-m-d H:i:s'))
            ->line('**Resolved at (UTC TIME):** ' . $this->outage->resolved_at->setTimezone('UTC')->format('Y-m-d H:i:s'))
            ->action('View Outages', url('/admin/outages'));
    }

    public function toSlack($notifiable): SlackMessage
    {
        $duration = $this->outage->occurred_at->diff($this->outage->resolved_at);

        return (new SlackMessage)
            ->success()
            ->content('✅ Site Restored: ' . $notifiable->name)
            ->attachment(function ($attachment) use ($notifiable, $duration) {
                $attachment->title('Site Back Online')
                    ->fields([
                        'Site' => $notifiable->name,
                        'URL' => $notifiable->url,
                        'Downtime' => $duration->format('%H:%I:%S'),
                        'Resolved at (UTC TIME)' => $this->outage->resolved_at->setTimezone('UTC')->format('Y-m-d H:i:s'),
                    ])
                    ->color('good');
            });
    }

    public function toDiscord($notifiable): DiscordMessage
    {
        $duration = $this->outage->occurred_at->diff($this->outage->resolved_at);

        return DiscordMessage::create()
            ->embed([
                'title' => '✅ Site Restored: ' . $notifiable->name,
                'description' => 'Your site is back online!',
                'color' => 5763719, // Green
                'fields' => [
                    ['name' => 'Site', 'value' => $notifiable->name, 'inline' => true],
                    ['name' => 'URL', 'value' => $notifiable->url, 'inline' => true],
                    ['name' => 'Downtime Duration', 'value' => $duration->format('%H:%I:%S'), 'inline' => false],
                    ['name' => 'Resolved at (UTC TIME)', 'value' => $this->outage->resolved_at->setTimezone('UTC')->format('Y-m-d H:i:s'), 'inline' => false],
                ],
                'timestamp' => $this->outage->resolved_at->toIso8601String(),
            ]);
    }

    public function toDatabase($notifiable): array
    {
        $duration = $this->outage->occurred_at->diff($this->outage->resolved_at);

        return [
            'title' => 'Site Restored: ' . $notifiable->name,
            'body' => 'Your site is back online after ' . $duration->format('%H:%I:%S') . ' of downtime.',
            'site_id' => $notifiable->id,
            'outage_id' => $this->outage->id,
            'icon' => 'heroicon-o-check-circle',
            'iconColor' => 'success',
            'actions' => [
                [
                    'name' => 'View Outage',
                    'url' => url('/outages'),
                ],
            ],
        ];
    }
}
