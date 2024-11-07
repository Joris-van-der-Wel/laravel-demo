<?php
declare(strict_types=1);

use App\Models\Share;
use App\Models\User;
use Facades\App\Services\ShareAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    #[Reactive]
    public string $shareId;

    public array $permissions = [];

    #[Validate('string')]
    public string $email = '';

    private function reloadPermissions(): void
    {
        $share = $this->share();

        $permissions = [];
        foreach ($share->userAccess()->orderBy('email')->get() as $user) {
            $permissions[$user->id] = [
                'email' => $user->email,
                'name' => $user->name,
                'permission' => $user->user_access->permission,
            ];
        }
        $this->permissions = $permissions;
    }

    public function mount(): void
    {
        $this->reloadPermissions();
    }

    #[Computed]
    public function share(): Share
    {
        return Share::where('id', $this->shareId)->firstOrFail();
    }

    public function save(): void
    {
        $this->validate();

        $share = DB::transaction(function () {
            $share = Share::where('id', $this->shareId)->firstOrFail();

            if (!ShareAuthorization::hasSharePermission($share, 'owner')) {
                return null;
            }

            $sync = [];
            foreach ($this->permissions as $userId => ['permission' => $permission]) {
                if ($userId !== $share->owner_id && ($permission === 'read' || $permission === 'write')) {
                    $sync[$userId] = ['permission' => $permission];
                }
            }

            $share->userAccess()->sync($sync);
            $share->addAuditLog('share_access_change');
            return $share;
        });

        if ($share) {
            $this->dispatch('share-updated-access', $share->id);
            unset($this->share);
            $this->reloadPermissions();
        }
    }

    public function addUser()
    {
        $share = $this->share();
        $user = User::where('email', $this->email)->first();
        if (!$user) {
            throw ValidationException::withMessages(['email' => __('No such user')]);
        }

        if (isset($this->permissions[$user->id]) || $user->id === $share->owner_id) {
            throw ValidationException::withMessages(['email' => __('This user has already been added')]);
        }

        $this->permissions[$user->id] = [
            'email' => $user->email,
            'name' => $user->name,
            'permission' => 'read',
        ];
        $this->email = '';
    }
}

?>
<div>
    <form wire:submit="save">
        @if (ShareAuthorization::hasSharePermission($this->share, 'owner'))
            <table class="w-full my-4">
                <thead>
                <tr>
                    <th class="text-left">User</th>
                    <th class="text-left">Permission</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="my-2" title="{{ $this->share->owner->name }}">{{ $this->share->owner->email }}</td>
                    <td class="h-10">{{ __('Owner') }}</td>
                </tr>
                @foreach ($permissions as $userId => ['email' => $email, 'name' => $name])
                    <tr>
                        <td class="my-2" title="{{ $name }}">{{ $email }}</td>
                        <td class="h-10">
                            <select
                                wire:model="permissions.{{$userId}}.permission"
                                name="permissions[{{ $userId }}]"
                                class="h-full"
                            >
                                <option value="none">{{ __('None') }}</option>
                                <option value="read">{{ __('Read') }}</option>
                                <option value="write">{{ __('Write') }}</option>
                            </select>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="my-2">
                <x-input-label for="email" :value="__('Add another user by email')"/>
                <div class="flex flex-row items-end">
                    <x-text-input
                        wire:model="email"
                        wire:keydown.enter.prevent.stop="addUser"
                        id="email"
                        class="block mt-1 w-full"
                        type="text"
                        name="email"
                    />
                    <x-secondary-button wire:click.prevent="addUser" class="ml-4 mb-[4px]">
                        Add
                    </x-secondary-button>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2"/>
            </div>

            <div class="flex justify-center mt-8">
                <x-primary-button class="ms-4">
                    {{ __('Save') }}
                </x-primary-button>
            </div>
        @else
            No access
        @endif
    </form>
</div>
