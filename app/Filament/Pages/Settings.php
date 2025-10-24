<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    public ?array $data = [];

    public function getHeading(): string
    {
        return 'Settings';
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->form->fill([
            'date_format' => $user->date_format ?? 'd/m/Y',
            'timezone' => $user->timezone ?? 'UTC',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Date & Time Preferences')
                    ->description('Configure how dates and times are displayed')
                    ->schema([
                        Select::make('date_format')
                            ->label('Date Format')
                            ->options([
                                'd/m/Y' => 'DD/MM/YYYY (European - 24/10/2025)',
                                'm/d/Y' => 'MM/DD/YYYY (American - 10/24/2025)',
                            ])
                            ->required()
                            ->helperText('Select your preferred date format'),
                        Select::make('timezone')
                            ->label('Timezone')
                            ->options([
                                'Europe/Rome' => 'Europe/Rome',
                                'Europe/London' => 'Europe/London',
                                'Europe/Paris' => 'Europe/Paris',
                                'Europe/Berlin' => 'Europe/Berlin',
                                'Europe/Madrid' => 'Europe/Madrid',
                                'America/New_York' => 'America/New York',
                                'America/Chicago' => 'America/Chicago',
                                'America/Denver' => 'America/Denver',
                                'America/Los_Angeles' => 'America/Los Angeles',
                                'Asia/Tokyo' => 'Asia/Tokyo',
                                'Asia/Shanghai' => 'Asia/Shanghai',
                                'Australia/Sydney' => 'Australia/Sydney',
                                'UTC' => 'UTC',
                            ])
                            ->searchable()
                            ->required()
                            ->helperText('Select your timezone. All dates are stored in UTC and converted for display'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'date_format' => $data['date_format'],
            'timezone' => $data['timezone'],
        ]);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Your date and time preferences have been updated.')
            ->send();
    }
}
