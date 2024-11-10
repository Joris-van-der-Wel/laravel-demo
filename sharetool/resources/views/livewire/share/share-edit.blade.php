<?php
declare(strict_types=1);

use App\Models\Share;
use App\Constants;
use Facades\App\Services\SecureRandom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    #[Reactive]
    public string $shareId;

    #[Validate('required', 'string')]
    public string $name = '';

    #[Validate('required', 'string')]
    public string $description = '';

    #[Validate('bool')]
    public bool $public = false;

    #[Validate('string')]
    public string $password = '';

    public function mount()
    {
        $share = $this->share();
        $this->name = $share->name;
        $this->description = $share->description;
        $this->public = $share->public_token !== null;
        $this->password = $share->password ? Constants::PASSWORD_SENTINEL : '';
    }

    #[Computed]
    public function share(): Share
    {
        return Share::where('id', $this->shareId)->firstOrFail();
    }

    public function save()
    {
        $this->validate();

        $share = DB::transaction(function () {
            // query the share again to avoid acting on and old cached instance of Share
            $share = Share::where('id', $this->shareId)->firstOrFail();

            Gate::authorize('update', $share);

            $share->name = $this->name;
            $share->description = $this->description;

            // Saving with public unchecked, and then later re-enabling public will cause a new token to be used.
            // This ensures that old links do not suddenly become valid again
            if ($this->public && !$share->public_token) {
                $share->public_token = SecureRandom::urlSafeToken(64);
            } else if (!$this->public) {
                $share->public_token = null;
            }

            if ($this->password !== Constants::PASSWORD_SENTINEL) {
                $share->password = $this->password ? Hash::make($this->password) : null;
            }
            $share->save();
            $share->addAuditLog('share_update');
            return $share;
        });

        if ($share) {
            $this->dispatch('share-updated', $share->id);
        }
    }
}

?>
<div>
    <form wire:submit="save">
        <div class="my-2">
            <x-input-label for="name" :value="__('Name')"/>
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required/>
            <x-input-error :messages="$errors->get('name')" class="mt-2"/>
        </div>

        <div class="my-2">
            <x-input-label for="description" :value="__('Description')"/>
            <x-text-input wire:model="description" id="description" class="block mt-1 w-full" type="text" name="description" required/>
            <x-input-error :messages="$errors->get('description')" class="mt-2"/>
        </div>

        <div class="my-4 flex flex-row gap-2">
            <input wire:model="public" name="public" id="public" type="checkbox"/>
            <x-input-label for="public" :value="__('Public')"/>
            <x-input-error :messages="$errors->get('public')" class="mt-2"/>
        </div>

        <div class="my-2">
            <x-input-label for="password" :value="__('Password')"/>
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password"/>
            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4">
                {{ __('Update') }}
            </x-primary-button>
        </div>
    </form>
</div>
