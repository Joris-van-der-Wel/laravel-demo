<?php

use App\Models\Share;
use App\Models\ShareAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

it('creates a new Share', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('share.share-create')
        ->set('name', 'Test name')
        ->set('description', 'Test description')
        ->set('public', true)
        ->set('password', 'Test password')
        ->call('save');

    $share = Share::firstOrFail();
    $this->assertEquals($share->owner_id, $user->id);
    $this->assertEquals($share->name, 'Test name');
    $this->assertEquals($share->description, 'Test description');
    $this->assertIsString($share->public_token);
    $this->assertTrue(Hash::check('Test password', $share->password));

    $logs = ShareAuditLog::all();
    $this->assertCount(1, $logs);

    $log = $logs[0];
    $this->assertEquals($log->share_id, $share->id);
    $this->assertNull($log->file_id);
    $this->assertEquals($log->user_id, $user->id);
    $this->assertEquals($log->type, 'share_create');
});
