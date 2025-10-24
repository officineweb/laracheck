<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load current users for the select
        $data['users'] = $this->record->users()->pluck('users.id')->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        // Update users relationship maintaining is_owner for existing owners
        if (isset($this->data['users']) && is_array($this->data['users'])) {
            $existingOwners = $this->record->users()
                ->wherePivot('is_owner', true)
                ->pluck('users.id')
                ->toArray();

            $usersData = [];
            foreach ($this->data['users'] as $userId) {
                $usersData[$userId] = ['is_owner' => in_array($userId, $existingOwners)];
            }

            $this->record->users()->sync($usersData);
        }
    }
}
