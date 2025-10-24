<?php

namespace App\Filament\Resources\Exceptions\Pages;

use App\Filament\Resources\Exceptions\ExceptionResource;
use App\Models\Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Response;

class ViewException extends ViewRecord
{
    protected static string $resource = ExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAsFixed')
                ->label('Mark as Fixed')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark exception as fixed')
                ->modalDescription('Are you sure you want to mark this exception as fixed?')
                ->modalSubmitActionLabel('Yes, mark as fixed')
                ->visible(fn() => $this->record->status === Exception::OPEN)
                ->action(function (): void {
                    $this->record->markAs(Exception::FIXED);
                    $this->refreshFormData([$this->record]);
                })
                ->successNotificationTitle('Exception marked as fixed'),

            Action::make('copyMarkdown')
                ->label('Copy to Markdown')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Copy exception as Markdown')
                ->modalDescription('The exception details will be copied to your clipboard in Markdown format.')
                ->modalSubmitActionLabel('Copy to Clipboard')
                ->action(function () {
                    $exception = $this->record;
                    $markdown = $this->generateMarkdown($exception);

                    $this->js(<<<JS
                                navigator.clipboard.writeText('{$this->escapeForJs($markdown)}').then(() => {
                                    console.log('Markdown copied to clipboard');
                                });
                            JS);
                })
                ->successNotificationTitle('Copied to clipboard!')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Markdown copied!')
                        ->body('Exception details have been copied to your clipboard.')
                ),
        ];
    }

    protected function generateMarkdown(Exception $exception): string
    {
        $markdown = "# Exception Report\n\n";

        $markdown .= "## Exception Details\n\n";
        $markdown .= "**ID:** {$exception->id}\n";
        $markdown .= "**Logged at (UTC TIME):** " . $exception->created_at->setTimezone('UTC')->format('Y-m-d H:i:s') . "\n";
        $markdown .= "**Status:** {$exception->status}\n\n";
        $markdown .= "**Exception Message:** {$exception->exception}\n\n";
        $markdown .= "**Error:** " . ($exception->error ?? 'N/A') . "\n\n";

        $markdown .= "## Context\n\n";
        $markdown .= "**Site:** " . ($exception->site?->name ?? 'N/A') . "\n";
        $markdown .= "**Host:** " . ($exception->host ?? 'N/A') . "\n";
        $markdown .= "**Environment:** " . ucfirst($exception->env ?? 'N/A') . "\n\n";

        $markdown .= "## Location\n\n";
        $markdown .= "**File:** " . ($exception->file ?? 'N/A') . "\n";
        $markdown .= "**Line:** " . ($exception->line ?? 'N/A') . "\n";
        $markdown .= "**Class:** " . ($exception->class ?? 'N/A') . "\n";
        $markdown .= "**Full URL:** " . ($exception->full_url ?? 'N/A') . "\n";
        $markdown .= "**Method:** " . ($exception->method ?? 'N/A') . "\n\n";

        $markdown .= "## Additional Data\n\n";

        if ($exception->storage) {
            $markdown .= "### Storage Info\n\n";
            $markdown .= "```json\n";
            $markdown .= json_encode($exception->storage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $markdown .= "\n```\n\n";
        }

        if ($exception->executor) {
            $markdown .= "### Executor Info\n\n";
            $markdown .= "```json\n";
            $markdown .= json_encode($exception->executor, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $markdown .= "\n```\n\n";
        }

        if ($exception->additional) {
            $markdown .= "### Additional Info\n\n";
            $markdown .= "```json\n";
            $markdown .= json_encode($exception->additional, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $markdown .= "\n```\n\n";
        }

        $markdown .= "---\n";
        $markdown .= "*Generated at (UTC TIME): " . now()->setTimezone('UTC')->format('Y-m-d H:i:s') . "*\n";

        return $markdown;
    }

    protected function escapeForJs(string $string): string
    {
        return str_replace(
            ["\\", "'", "\n", "\r", "\t"],
            ["\\\\", "\\'", "\\n", "\\r", "\\t"],
            $string
        );
    }
}
