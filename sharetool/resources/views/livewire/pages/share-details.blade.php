<?php
declare(strict_types=1);

use App\Models\Share;
use App\ShareAccessDenyReason;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
class extends Component {
    #[Locked]
    public string $shareId; // from route

    #[Locked]
    public ?string $publicToken = null; // from route

    public ?string $selectedFileId = null;

    #[Computed]
    public function share(): Share
    {
        // This component is used in two routes. The first requires a session using the regular
        // auth middleware. The second does not check for a session, but requires a publicToken

        $share = Share::where('id', $this->shareId)->firstOrFail();
        try {
            Gate::authorize('view', [$share, $this->publicToken]);
            return $share;

        } catch (AuthorizationException $e) {
            if ($e->getCode() === ShareAccessDenyReason::InvalidSharePassword) {
                if ($this->publicToken) {
                    $url = route('publicShare.login', ['shareId' => $this->shareId, 'publicToken' => $this->publicToken]);
                } else {
                    $url = route('share.login', ['shareId' => $this->shareId]);
                }
                $this->redirect($url);
                return new Share;
            }

            throw $e;
        }
    }

    #[On('file-select')]
    public function handleFileSelect(?string $fileId): void
    {
        $this->selectedFileId = $fileId;
        $this->dispatch('open-modal', 'file-details');
    }

    #[On('file-created')]
    public function handleFileCreated(string $fileId): void
    {
        $this->dispatch('close-modal', 'file-create');
    }

    #[On('share-updated')]
    public function handleShareUpdated(string $shareId): void
    {
        $this->dispatch('close-modal', 'share-edit');
    }

    #[On('share-updated-access')]
    public function handleShareUpdatedAccess(string $shareId): void
    {
        $this->dispatch('close-modal', 'share-edit-access');
    }

    public function deleteShare(): void
    {
        $share = $this->share();

        Gate::authorize('delete', $share);

        DB::transaction(function () use ($share) {
            $share->addAuditLog('share_delete');
            $share->delete();
        });
        $this->redirectRoute('share.overview');
    }
}
?>
<div>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex-grow">
                    {{ __('Share') }}: {{ $this->share->name }}
                </h2>
                <div class="flex flex-row gap-2">
                    @can('viewAudit', $this->share)
                        <x-secondary-button wire:click.prevent="$dispatch('open-modal', 'share-audit')">
                            {{ __('Audit Log…') }}
                        </x-secondary-button>
                    @endcan

                    @can('update', $this->share)
                        <x-secondary-button wire:click.prevent="$dispatch('open-modal', 'share-edit')">
                            {{ __('Edit…') }}
                        </x-secondary-button>
                    @endcan

                    @can('updateAccess', $this->share)
                        <x-secondary-button wire:click.prevent="$dispatch('open-modal', 'share-edit-access')">
                            {{ __('Permissions…') }}
                        </x-secondary-button>
                    @endcan

                    @can ('delete', $this->share)
                        <x-danger-button
                            wire:click.prevent="$dispatch('open-modal', 'share-delete')"
                            type="button"
                        >
                            {{ __('Delete…') }}
                        </x-danger-button>
                   @endcan
                </div>
            </div>
            <div>
                {{ __('Created') }}: {{ $this->share->created_at?->diffForHumans() }}<br/>
                {{ __('Last change') }}: {{ $this->share->updated_at?->diffForHumans() }}<br/>
                {{ __('Public') }}:
                @if ($this->share->public_token)
                    {{ __('Yes') }}
                    <a
                        class="text-blue-700"
                        href="{{ route('publicShare.details', ['shareId' => $this->share->id, 'publicToken' => $this->share->public_token]) }}"
                        data-copy-link
                    >
                        {{ __('(copy link)') }}
                    </a>
                @else
                    {{ __('No') }}
                @endif
                <br/>
                {{ __('Password protected') }}: {{ $this->share->password ? __('Yes') : __('No') }}<br/>
            </div>
            <p class="text-gray-500">
                {{ $this->share->description }}
            </p>
        </div>
    </header>

    <div class="my-12 max-w-7xl mx-auto sm:px-6 lg:px-8 flex justify-center">
        @can ('createFile', $this->share)
            <x-primary-button
                type="button"
                wire:click.prevent="$dispatch('open-modal', 'file-create')"
            >
                {{ __('Upload new file…') }}
            </x-primary-button>
        @endcan
    </div>

    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <livewire:share.file-list :shareId="$shareId"/>
        </div>
    </div>

    <x-modal name="file-details" focusable>
        @if ($selectedFileId)
            <livewire:share.file-details :shareId="$shareId" :fileId="$selectedFileId"/>
        @endif
    </x-modal>

    <x-modal name="file-create" focusable>
        <div class="p-6">
            <h3 class="text-xl">{{ __('Create new file') }}</h3>
            <livewire:share.file-create :shareId="$shareId"/>
        </div>
    </x-modal>

    <x-modal name="share-edit" focusable>
        <div class="p-6">
            <h3 class="text-xl">{{ __('Edit Share') }}</h3>
            <livewire:share.share-edit :shareId="$shareId"/>
        </div>
    </x-modal>

    <x-modal name="share-edit-access" focusable>
        <div class="p-6">
            <h3 class="text-xl">{{ __('Edit Permissions') }}</h3>
            <livewire:share.share-edit-access :shareId="$shareId"/>
        </div>
    </x-modal>

    <x-modal name="share-audit" focusable>
        <div class="p-6">
            <h3 class="text-xl">{{ __('Audit Log') }}</h3>
            <livewire:share.share-audit-log :shareId="$shareId"/>
        </div>
    </x-modal>

    <x-delete-modal name="share-delete" action="deleteShare">
        {{ __('Delete share') }}: {{ $this->share->name }}
    </x-delete-modal>
</div>
