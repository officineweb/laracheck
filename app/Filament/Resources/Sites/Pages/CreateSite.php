<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\SiteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-assign the creator as owner if they're not an admin
        if (! auth()->user()->isAdmin()) {
            $data['users'] = [auth()->id()];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Attach users with is_owner flag
        if (isset($this->data['users']) && is_array($this->data['users'])) {
            $creator = auth()->id();
            $usersData = [];

            foreach ($this->data['users'] as $userId) {
                $usersData[$userId] = ['is_owner' => $userId == $creator];
            }

            $this->record->users()->sync($usersData);
        } else {
            // If no users specified, attach the creator as owner
            $this->record->users()->attach(auth()->id(), ['is_owner' => true]);
        }
    }
}
