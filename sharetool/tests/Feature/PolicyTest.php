<?php

use App\Models\Share;
use App\Models\User;
use App\ShareAccessDenyReason;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

beforeEach(function() {
    $this->user = User::factory()->create();
});

it('allows a user to view a Share if they are granted the read permission', function() {
    $share = Share::factory()->create(['password' => null]);
    $share->userAccess()->attach($this->user, ['permission' => 'read']);
    $this->actingAs($this->user);
    $this->assertTrue(Gate::allows('view', $share));
});

it('allows a guest to view a Share if they provide the proper public token', function() {
    $share = Share::factory()->create(['public_token' => 'testtoken', 'password' => null]);
    $this->assertTrue(Gate::allows('view', [$share, 'testtoken']));
});

it('denies a guest to view a Share if they provide an invalid public token', function() {
    $share = Share::factory()->create(['public_token' => 'testtoken', 'password' => null]);
    $response = Gate::inspect('view', [$share, 'wrongtoken']);
    $this->assertTrue($response->denied());
    $this->assertEquals($response->code(), ShareAccessDenyReason::PublicTokenIncorrect);
});

describe('Password protected share', function() {
    it('allows a user to view without a password if they are the owner', function() {
        $share = Share::factory()->create(['owner_id' => $this->user->id, 'password' => Hash::make('testpassword')]);
        $this->actingAs($this->user);
        $this->assertTrue(Gate::allows('view', $share));
    });

    it('allows a user to view if they provide the proper password', function() {
        $share = Share::factory()->create(['password' => Hash::make('testpassword')]);
        $share->userAccess()->attach($this->user, ['permission' => 'read']);
        $this->actingAs($this->user);
        session(["share-password.$share->id" => $share->password]);
        $this->assertTrue(Gate::allows('view', $share));
    });

    describe('Incorrect password', function() {
        beforeEach(function() {
            $share = Share::factory()->create(['password' => Hash::make('testpassword')]);
            $share->userAccess()->attach($this->user, ['permission' => 'read']);
            $this->actingAs($this->user);
            $this->share = $share;
        });

        it('denies a user to view if the password is not set', function() {
            $response = Gate::inspect('view', $this->share);
            $this->assertTrue($response->denied());
            $this->assertEquals($response->code(), ShareAccessDenyReason::InvalidSharePassword);
        });

        it('denies a user to view if the password is invalid', function() {
            session(["share-password.$this->share->id" => Hash::make('invalidpassword')]);
            $response = Gate::inspect('view', $this->share);
            $this->assertTrue($response->denied());
            $this->assertEquals($response->code(), ShareAccessDenyReason::InvalidSharePassword);
        });

        it('denies a user to view if an old hash is used', function() {
            session(["share-password.$this->share->id" => Hash::make('testpassword')]);
            $response = Gate::inspect('view', $this->share);
            $this->assertTrue($response->denied());
            $this->assertEquals($response->code(), ShareAccessDenyReason::InvalidSharePassword);
        });
    });
});

it('Allows any authenticated user to create new shares', function() {
    $this->actingAs($this->user);
    $this->assertTrue(Gate::allows('create', Share::class));
});

describe('Owner access', function() {
    beforeEach(function() {
        $this->share = Share::factory()->create(['owner_id' => $this->user->id, 'password' => null]);
        $this->actingAs($this->user);
    });

    it('allows a user to view a Share if they are the owner', function() {
        $this->assertTrue(Gate::allows('view', $this->share));
    });

    it('allows a user to view the audit log of a Share if they are the owner', function() {
        $this->assertTrue(Gate::allows('viewAudit', $this->share));
    });

    it('allows a user to update the access of a Share if they are the owner', function() {
        $this->assertTrue(Gate::allows('updateAccess', $this->share));
    });

    it('allows a user to update a Share if they are the owner', function() {
        $this->assertTrue(Gate::allows('update', $this->share));
    });

    it('allows a user to delete a Share if they are the owner', function() {
        $this->assertTrue(Gate::allows('delete', $this->share));
    });
});

// todo test other policy methods and test FilePolicy
