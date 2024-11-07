<?php
declare(strict_types=1);

use App\Models\Share;
use Facades\App\Services\SecureRandom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    #[Validate('required', 'string')]
    public string $name = '';

    #[Validate('required', 'string')]
    public string $description = '';

    #[Validate('bool')]
    public bool $public = false;

    #[Validate('string')]
    public string $password = '';

    public function save()
    {
        if (!auth()->user()) {
            return;
        }

        $this->validate();

        $share = DB::transaction(function () {
            $share = new Share;
            $share->id = Str::ulid()->toBase32();
            $share->owner_id = auth()->user()->id;
            $share->name = $this->name;
            $share->description = $this->description;
            $share->public_token = $this->public ? SecureRandom::urlSafeToken(64) : null;
            $share->password = $this->password ? Hash::make($this->password) : null;
            $share->save();
            $share->addAuditLog('share_create');
            return $share;
        });
        $this->dispatch('share-created', $share->id);
    }
}

?>
<div>
    <form wire:submit="save">
        <div class="my-2">
            <x-input-label for="name" value="Name"/>
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required/>
            <x-input-error :messages="$errors->get('name')" class="mt-2"/>
        </div>

        <div class="my-2">
            <x-input-label for="description" value="Description"/>
            <x-text-input wire:model="description" id="description" class="block mt-1 w-full" type="text" name="description" required/>
            <x-input-error :messages="$errors->get('description')" class="mt-2"/>
        </div>

        <div class="my-2 flex flex-row gap-2">
            <input wire:model="public" name="public" id="public" type="checkbox"/>
            <x-input-label for="public" value="Public"/>
            <x-input-error :messages="$errors->get('public')" class="mt-2"/>
        </div>

        <div class="my-2">
            <x-input-label for="password" value="Password"/>
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password"/>
            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4">
                Create
            </x-primary-button>
        </div>
    </form>
</div>
