<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Settings;
use App\Filament\Resources\Users\UserResource;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->brandName('Laracheck')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('2.5rem')
            ->topNavigation()
            ->colors([
                'primary' => Color::Slate,
            ])
            ->favicon('logo.png')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->profile()
            ->passwordReset()
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->recoveryCodeCount(8)
                    ->codeWindow(8)
                    ->brandName(config('app.name')),
                EmailAuthentication::make()
                    ->codeExpiryMinutes(4),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\DashboardStatsWidget::class,
                \App\Filament\Widgets\ActiveOutagesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'user_management' => MenuItem::make()
                    ->label('Users')
                    ->url(fn(): string => UserResource::getUrl('index'))
                    ->icon('heroicon-o-users')
                    ->visible(fn(): bool => auth()->user()?->isAdmin() ?? false),
                'settings' => MenuItem::make()
                    ->label('Settings')
                    ->url(fn(): string => Settings::getUrl())
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);
    }
}
