<?php

namespace Tests\Feature;

use App\Filament\Resources\Sites\Pages\ListSites;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_all_sites(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $site1 = Site::factory()->create(['name' => 'Site 1']);
        $site2 = Site::factory()->create(['name' => 'Site 2']);
        $site3 = Site::factory()->create(['name' => 'Site 3']);

        $this->actingAs($admin);

        Livewire::test(ListSites::class)
            ->assertCanSeeTableRecords([$site1, $site2, $site3]);
    }

    public function test_user_can_only_see_their_sites(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $otherUser = User::factory()->create(['is_admin' => false]);

        $userSite1 = Site::factory()->create(['name' => 'User Site 1']);
        $userSite2 = Site::factory()->create(['name' => 'User Site 2']);
        $otherSite = Site::factory()->create(['name' => 'Other Site']);

        $userSite1->users()->attach($user->id, ['is_owner' => true]);
        $userSite2->users()->attach($user->id, ['is_owner' => false]);
        $otherSite->users()->attach($otherUser->id, ['is_owner' => true]);

        $this->actingAs($user);

        Livewire::test(ListSites::class)
            ->assertCanSeeTableRecords([$userSite1, $userSite2])
            ->assertCanNotSeeTableRecords([$otherSite]);
    }

    public function test_user_can_only_edit_sites_they_own(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $ownedSite = Site::factory()->create();
        $collaboratorSite = Site::factory()->create();
        $otherSite = Site::factory()->create();

        $ownedSite->users()->attach($user->id, ['is_owner' => true]);
        $collaboratorSite->users()->attach($user->id, ['is_owner' => false]);

        $this->assertTrue($user->can('update', $ownedSite));
        $this->assertFalse($user->can('update', $collaboratorSite));
        $this->assertFalse($user->can('update', $otherSite));
    }

    public function test_user_can_only_delete_sites_they_own(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $ownedSite = Site::factory()->create();
        $collaboratorSite = Site::factory()->create();

        $ownedSite->users()->attach($user->id, ['is_owner' => true]);
        $collaboratorSite->users()->attach($user->id, ['is_owner' => false]);

        $this->assertTrue($user->can('delete', $ownedSite));
        $this->assertFalse($user->can('delete', $collaboratorSite));
    }

    public function test_user_can_view_sites_they_are_part_of(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $ownedSite = Site::factory()->create();
        $collaboratorSite = Site::factory()->create();
        $otherSite = Site::factory()->create();

        $ownedSite->users()->attach($user->id, ['is_owner' => true]);
        $collaboratorSite->users()->attach($user->id, ['is_owner' => false]);

        $this->assertTrue($user->can('view', $ownedSite));
        $this->assertTrue($user->can('view', $collaboratorSite));
        $this->assertFalse($user->can('view', $otherSite));
    }
}
