<?php

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Site Information')
                    ->description('Basic information about the site')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextInput::make('name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('My Awesome Website'),
                        TextInput::make('url')
                            ->label('Site URL')
                            ->url()
                            ->required()
                            ->placeholder('https://example.com')
                            ->helperText('The full URL where your site is hosted'),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Optional description of your site (e.g., production, staging, etc.)'),
                    ]),

                Section::make('API Configuration')
                    ->description('Use this API key to connect your site to Laracheck')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('key')
                            ->label('API Key')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->helperText('This key is automatically generated when you create the site. Click the copy icon to copy it.'),
                    ])
                    ->hidden(fn($record) => $record === null)
                    ->collapsible(),

                Section::make('Notification Settings')
                    ->description('Choose how you want to receive exception notifications')
                    ->icon('heroicon-o-bell')
                    ->collapsible()
                    ->schema([
                        Toggle::make('receive_email')
                            ->label('Email Notifications')
                            ->helperText('Receive notifications via email')
                            ->default(true)
                            ->inline(false),

                        Fieldset::make('Webhooks')
                            ->schema([
                                TextInput::make('slack_webhook')
                                    ->label('Slack Webhook URL')
                                    ->url()
                                    ->placeholder('https://hooks.slack.com/services/...')
                                    ->helperText('Get webhook URL from Slack settings'),
                                TextInput::make('discord_webhook')
                                    ->label('Discord Webhook URL')
                                    ->url()
                                    ->placeholder('https://discord.com/api/webhooks/...')
                                    ->helperText('Get webhook URL from Discord channel settings'),
                            ]),
                    ]),

                Section::make('Uptime Monitoring')
                    ->description('Monitor your site availability and get notified when it goes down')
                    ->icon('heroicon-o-signal')
                    ->collapsible()
                    ->schema([
                        Toggle::make('enable_uptime_check')
                            ->label('Enable Uptime Monitoring')
                            ->helperText('Check site availability every minute')
                            ->default(true)
                            ->inline(false)
                            ->live(),

                        Fieldset::make('Check Configuration')
                            ->schema([
                                TextInput::make('check_url')
                                    ->label('Check URL')
                                    ->url()
                                    ->placeholder(fn($get) => $get('url') ?: 'https://example.com/status')
                                    ->helperText('Leave empty to use the main site URL. You can specify a custom endpoint like /status or /up'),
                            ])
                            ->visible(fn($get) => $get('enable_uptime_check')),

                        Fieldset::make('Outage Notifications')
                            ->schema([
                                TextInput::make('email_outage')
                                    ->label('Outage Alert Email')
                                    ->email()
                                    ->placeholder('admin@example.com')
                                    ->helperText('Email address to notify when site goes down'),
                                TextInput::make('email_resolved')
                                    ->label('Recovery Alert Email')
                                    ->email()
                                    ->placeholder('admin@example.com')
                                    ->helperText('Email address to notify when site comes back online'),
                            ])
                            ->visible(fn($get) => $get('enable_uptime_check')),
                    ]),

                Section::make('Assigned Users')
                    ->description('Select which users can access this site (admins always have access)')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Select::make('users')
                            ->label('Users')
                            ->relationship(
                                name: 'users',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->where('is_admin', false)
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Only regular users are listed here. Administrators already have access to all sites.'),
                    ])
                    ->visible(fn() => auth()->user()?->isAdmin())
                    ->collapsible(),
            ]);
    }
}
