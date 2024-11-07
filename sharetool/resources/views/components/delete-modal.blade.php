@props([
    'name',
    'show' => false,
    'action',
])

<x-modal name="{{ $name }}" focusable>
    <div class="p-6">
        <h3 class="text-xl">{{ $slot }}</h3>
        <p class="my-2">{{ __('Are you sure?') }}</p>
        <div class="flex flex-row gap-4">
            <x-secondary-button x-on:click.prevent="$dispatch('close-modal', {{ json_encode($name) }})">
                {{ __('Cancel') }}
            </x-secondary-button>
            <x-danger-button type="button" wire:click="{{ $action }}">
                {{ __('Delete') }}
            </x-danger-button>
        </div>
    </div>
</x-modal>
