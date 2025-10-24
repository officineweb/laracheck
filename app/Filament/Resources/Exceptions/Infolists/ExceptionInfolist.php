<?php

namespace App\Filament\Resources\Exceptions\Infolists;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExceptionInfolist
{
    protected static function makeCopyable(string $text, string $originalText = null): \Illuminate\Support\HtmlString
    {
        $copyText = $originalText ?? strip_tags($text);

        return new \Illuminate\Support\HtmlString(
            '<div class="flex items-center gap-2" style="display: flex;">
                <div>' . $text . '</div>
                <button 
                    type="button"
                    x-data="{ copied: false }" 
                    @click.prevent="
                        navigator.clipboard.writeText(\'' . addslashes($copyText) . '\');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0"
                    style="display: inline-flex; align-items: center; justify-content: center; margin-left: 6px;"
                    title="Copy to clipboard"
                >
                    <svg x-show="!copied" class="w-4 h-4" style="width: 16px; height: 16px; display: block;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4 text-success-600" style="width: 16px; height: 16px; display: block;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </div>'
        );
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Exception Details')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->description('Main exception information')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        Placeholder::make('id')
                            ->label('ID')
                            ->content(fn($record) => self::makeCopyable(
                                '<span class="font-mono text-sm">' . e($record->id) . '</span>',
                                $record->id
                            ))
                            ->columnSpanFull(),
                        Placeholder::make('created_at')
                            ->label('Logged at')
                            ->content(fn($record) => \App\Helpers\DateHelper::format($record->created_at))
                            ->columnSpan(1),
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn($record) => $record->status === 'OPEN' ? '🔴 Open' : '✅ Fixed')
                            ->columnSpan(1),
                        Placeholder::make('exception')
                            ->label('Exception Message')
                            ->content(function ($record) {
                                $truncated = \Illuminate\Support\Str::limit($record->exception, 200);

                                return self::makeCopyable(
                                    '<span class="break-words">' . e($truncated) . '</span>',
                                    $record->exception
                                );
                            })
                            ->columnSpan(1),
                        Placeholder::make('error')
                            ->label('Error')
                            ->content(function ($record) {
                                $error = $record->error ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($error, 200);

                                return self::makeCopyable(
                                    '<span class="break-words">' . e($truncated) . '</span>',
                                    $error
                                );
                            })
                            ->columnSpan(1),
                    ]),

                Section::make('Context')
                    ->icon('heroicon-o-information-circle')
                    ->description('Site & environment')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('site_name')
                            ->label('Site')
                            ->content(function ($record) {
                                if (! $record->site) {
                                    return self::makeCopyable('N/A');
                                }

                                $url = route('filament.admin.resources.sites.edit', ['record' => $record->site->id]);

                                return new \Illuminate\Support\HtmlString(
                                    '<a href="' . $url . '" class="text-primary-600 hover:text-primary-700 underline font-medium">' . e($record->site->name) . '</a>'
                                );
                            }),
                        Placeholder::make('host')
                            ->label('Host')
                            ->content(function ($record) {
                                $host = $record->host ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($host, 50);

                                return self::makeCopyable(
                                    '<span class="break-all">' . e($truncated) . '</span>',
                                    $host
                                );
                            }),
                        Placeholder::make('env')
                            ->label('Environment')
                            ->content(fn($record) => ucfirst($record->env ?? 'N/A')),
                    ]),

                Section::make('Location')
                    ->icon('heroicon-o-code-bracket')
                    ->description('Where the exception occurred')
                    ->columns(3)
                    ->columnSpan(3)
                    ->schema([
                        Placeholder::make('file')
                            ->label('File')
                            ->content(function ($record) {
                                $file = $record->file ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($file, 100);

                                return self::makeCopyable(
                                    '<span class="break-all font-mono text-xs">' . e($truncated) . '</span>',
                                    $file
                                );
                            })
                            ->columnSpan(2),
                        Placeholder::make('line')
                            ->label('Line')
                            ->content(function ($record) {
                                $line = $record->line ?? 'N/A';

                                return self::makeCopyable($line);
                            })
                            ->columnSpan(1),
                        Placeholder::make('class')
                            ->label('Class')
                            ->content(function ($record) {
                                $class = $record->class ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($class, 100);

                                return self::makeCopyable(
                                    '<span class="break-all font-mono text-xs">' . e($truncated) . '</span>',
                                    $class
                                );
                            })
                            ->columnSpanFull(),
                        Placeholder::make('full_url')
                            ->label('Full URL')
                            ->content(function ($record) {
                                $url = $record->full_url ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($url, 150);

                                return self::makeCopyable(
                                    '<span class="break-all font-mono text-xs">' . e($truncated) . '</span>',
                                    $url
                                );
                            })
                            ->columnSpan(2),
                        Placeholder::make('method')
                            ->label('Method')
                            ->content(function ($record) {
                                $method = $record->method ?? 'N/A';

                                return self::makeCopyable($method);
                            })
                            ->columnSpan(1),
                    ]),

                Section::make('User Information')
                    ->icon('heroicon-o-user')
                    ->description('User who triggered the exception')
                    ->columns(3)
                    ->columnSpan(3)
                    ->collapsed()
                    ->schema([
                        Placeholder::make('user_id')
                            ->label('User ID')
                            ->content(function ($record) {
                                $userId = $record->user['id'] ?? 'Guest';

                                return self::makeCopyable($userId);
                            })
                            ->columnSpan(1),
                        Placeholder::make('user_email')
                            ->label('User Email')
                            ->content(function ($record) {
                                $email = $record->user['email'] ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($email, 50);

                                return self::makeCopyable(
                                    '<span class="break-all">' . e($truncated) . '</span>',
                                    $email
                                );
                            })
                            ->columnSpan(1),
                        Placeholder::make('user_name')
                            ->label('User Name')
                            ->content(function ($record) {
                                $name = $record->user['name'] ?? 'N/A';
                                $truncated = \Illuminate\Support\Str::limit($name, 50);

                                return self::makeCopyable(
                                    '<span class="break-words">' . e($truncated) . '</span>',
                                    $name
                                );
                            })
                            ->columnSpan(1),
                    ]),

                Section::make('Additional Data')
                    ->icon('heroicon-o-circle-stack')
                    ->description('Storage and executor information')
                    ->columns(1)
                    ->columnSpan(3)
                    ->collapsed()
                    ->schema([
                        Placeholder::make('storage_info')
                            ->label('Storage Info')
                            ->content(function ($record) {
                                if (! $record->storage) {
                                    return 'N/A';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg bg-gray-950 p-4 font-mono text-xs text-gray-100 dark:bg-gray-900 overflow-x-auto"><pre>' . json_encode($record->storage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre></div>'
                                );
                            }),
                        Placeholder::make('executor_info')
                            ->label('Executor Info')
                            ->content(function ($record) {
                                if (! $record->executor) {
                                    return 'N/A';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg bg-gray-950 p-4 font-mono text-xs text-gray-100 dark:bg-gray-900 overflow-x-auto"><pre>' . json_encode($record->executor, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre></div>'
                                );
                            }),
                        Placeholder::make('additional_info')
                            ->label('Additional Info')
                            ->content(function ($record) {
                                if (! $record->additional) {
                                    return 'N/A';
                                }

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg bg-gray-950 p-4 font-mono text-xs text-gray-100 dark:bg-gray-900 overflow-x-auto"><pre>' . json_encode($record->additional, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre></div>'
                                );
                            }),
                    ]),
            ]);
    }
}
