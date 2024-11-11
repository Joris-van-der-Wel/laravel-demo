<?php

use App\Models\Share;
use App\Models\User;
use Livewire\Volt\Volt;

it('requires authentication', function () {
    $response = $this->get('/shares');
    $response->assertRedirect('/login');
});

it('renders the share-overview volt component', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/shares');

    $response
        ->assertSeeVolt('pages.share-overview')
        ->assertStatus(200);
});

it('renders only shares accessible to the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // $user is the owner; Should be visible
    $share1 = Share::factory()->create(['name' => 'Owns share', 'owner_id' => $user->id]);

    // $user has read access; Should be visible
    $share2 = Share::factory()->create(['name' => 'Has read access', 'owner_id' => $otherUser->id]);
    $share2->userAccess()->attach($user, ['permission' => 'read']);

    // $user has write access; Should be visible
    $share3 = Share::factory()->create(['name' => 'Has write access', 'owner_id' => $otherUser->id]);
    $share3->userAccess()->attach($user, ['permission' => 'read']);

    // $user has no access access; Should not be visible
    Share::factory()
        ->create(['name' => 'Has no access', 'owner_id' => $otherUser->id]);

    $this->actingAs($user);

    $component = Volt::test('pages.share-overview');

    $this->assertEquals(
        array_map(fn ($share) => $share->id, $component->shares->all()),
        [$share1->id, $share2->id, $share3->id],
    );
});
