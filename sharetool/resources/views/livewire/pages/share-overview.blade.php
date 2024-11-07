<?php
declare(strict_types=1);

use App\Models\Share;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
class extends Component {
    #[Computed]
    public function shares(): Collection
    {
        return Share::whereUserHasAccess(auth()->user())->get();
    }

    #[On('share-created')]
    public function handleShareCreated(string $shareId): void
    {
        $this->dispatch('close-modal', 'share-create');
    }
}

?>
<div>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Shares') }}
            </h2>
        </div>
    </header>

    <div class="my-12 max-w-7xl mx-auto sm:px-6 lg:px-8 flex justify-center">
        <x-primary-button type="button" wire:click.prevent="$dispatch('open-modal', 'share-create')">
            {{ __('Create new share…') }}
        </x-primary-button>
    </div>

    <div class="my-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="w-full">
                        <thead>
                        <tr>
                            <th class="py-2">{{ __('Name') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Public') }}</th>
                            <th>{{ __('Password') }}</th>
                            <th>{{ __('Owner') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($this->shares as $share)
                            <tr>
                                <td class="py-2">
                                    <a class="block" href="{{ route('share.details', ['shareId' => $share->id]) }}">
                                        {{ $share->name }}
                                    </a>
                                </td>
                                <td>
                                    <a class="block truncate max-w-xl" href="{{ route('share.details', ['shareId' => $share->id]) }}">
                                        {{ $share->description }}
                                    </a>
                                </td>
                                <td>
                                    {{ $share->public_token === null ? '' : __('Yes') }}
                                </td>
                                <td>
                                    {{ $share->password === null ? '' : __('Yes') }}
                                </td>
                                <td>
                                    {{ $share->owner_id === auth()->user()?->id ? __('Yes') : '' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-modal name="share-create" focusable>
        <div class="p-6">
            <h3 class="text-xl">{{ __('Create new share') }}</h3>
            <livewire:share.share-create/>
        </div>
    </x-modal>
</div>
