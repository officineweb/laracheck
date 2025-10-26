<?php

namespace App\Filament\Resources\Sites\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $lines = [];

                        if ($record->description) {
                            $lines[] = $record->description;
                        }

                        $openExceptionsCount = $record->unreadExceptions()->count();
                        if ($openExceptionsCount > 0) {
                            $lines[] = '🔴 ' . $openExceptionsCount . ' open exception(s)';
                        }

                        return ! empty($lines) ? new \Illuminate\Support\HtmlString(implode('<br>', $lines)) : null;
                    })
                    ->badge()
                    ->color(fn($record) => $record->activeOutage() ? 'danger' : 'success')
                    ->wrap(),
                TextColumn::make('url')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->color('gray')
                    ->copyable()
                    ->description(function ($record) {
                        if ($record->checked_at && $record->enable_uptime_check) {
                            return 'Last check: ' . $record->checked_at->diffForHumans();
                        }

                        return null;
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('setup')
                        ->label('Setup')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->modalWidth('4xl')
                        ->infolist(fn($record) => [
                            Section::make('API Key')
                                ->description('Use this key to configure the exception client')
                                ->schema([
                                    TextEntry::make('key')
                                        ->label('Your API Key')
                                        ->copyable()
                                        ->helperText('This key is automatically generated when you create the site. Click to copy.')
                                        ->fontFamily('mono')
                                        ->weight(FontWeight::Bold)
                                        ->color('success')
                                        ->size('xl'),
                                ]),
                            Section::make('Installation Instructions')
                                ->description('Follow these steps to integrate Laracheck with your application')
                                ->schema([
                                    TextEntry::make('instructions')
                                        ->label('')
                                        ->state(fn($record) => new \Illuminate\Support\HtmlString(
                                            '<div class="prose prose-sm dark:prose-invert max-w-none">
                                                <h3>1. Install the package</h3>
                                                <pre class="bg-gray-950 text-white p-4 rounded-lg overflow-x-auto"><code>composer require officineweb/laracheck-client</code></pre>

                                                <h3>2. Publish the configuration</h3>
                                                <pre class="bg-gray-950 text-white p-4 rounded-lg overflow-x-auto"><code>php artisan vendor:publish --tag=laracheck-config</code></pre>

                                                <h3>3. Configure your .env file</h3>
                                                <pre class="bg-gray-950 text-white p-4 rounded-lg overflow-x-auto"><code>LARACHECK_KEY=' . $record->key . '
LARACHECK_URL=' . url('/api') . '</code></pre>

                                                <h3>4. Enable exception tracking in bootstrap/app.php</h3>
                                                <p>Add exception tracking to your application configuration:</p>
                                                <pre class="bg-gray-950 text-white p-4 rounded-lg overflow-x-auto"><code>use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    -&gt;withRouting(
        web: __DIR__.\'/../routes/web.php\',
        commands: __DIR__.\'/../routes/console.php\',
        health: \'/up\',
    )
    -&gt;withMiddleware(function (Middleware $middleware): void {
        //
    })
    -&gt;withExceptions(function (Exceptions $exceptions): void {
        // 👇 Add this line
        app(\'laracheck\')-&gt;track($exceptions);
    })
    -&gt;create();</code></pre>

                                                <h3>5. Done! 🎉</h3>
                                                <p>Exceptions will now be automatically tracked and sent to Laracheck.</p>

                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">
                                                    For more information, visit: <a href="https://github.com/officineweb/laracheck" target="_blank" class="text-blue-600 hover:underline">Documentation</a>
                                                </p>
                                            </div>'
                                        )),
                                ]),
                        ])
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                    Action::make('visit')
                        ->label('Visit Site')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn($record) => $record->url)
                        ->openUrlInNewTab(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
