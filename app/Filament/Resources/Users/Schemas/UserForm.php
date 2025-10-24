<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->rules([
                        fn (string $context): \Illuminate\Validation\Rules\Password => $context === 'create'
                            ? \App\Helpers\Password::validation()
                            : \Illuminate\Validation\Rules\Password::default(),
                    ])
                    ->helperText('Min 8 characters with uppercase, lowercase, numbers, and symbols. Leave blank to keep current password.'),
                Toggle::make('is_admin')
                    ->label('Administrator')
                    ->helperText('Administrators can manage all sites and users. Note: Site assignments below will be hidden for administrators.')
                    ->default(false)
                    ->live(),
                Toggle::make('receive_email')
                    ->label('Email Notifications')
                    ->helperText('Receive email notifications for exceptions')
                    ->default(true),
            ]);
    }
}
