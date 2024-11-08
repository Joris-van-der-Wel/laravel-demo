<?php
declare(strict_types=1);

use App\Events\FileDeleted;
use App\Models\File;
use App\Models\Share;
use Facades\App\Services\ShareAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    #[Reactive]
    public string $shareId;

    #[Locked]
    #[Reactive]
    public string $fileId;

    #[Computed]
    public function share(): Share
    {
        return Share::where('id', $this->shareId)->firstOrFail();
    }

    #[Computed]
    public function file(): ?File
    {
        return $this->share()->files()->where('id', $this->fileId)->first();
    }

    public function deleteFile(): void
    {
        $file = $this->file();

        $this->dispatch('close-modal', 'file-delete');
        $this->dispatch('close-modal', 'file-details');

        DB::transaction(function () use ($file) {
            $file->addAuditLog('file_delete');
            $file->delete();
        });
        Storage::disk('user-uploads')->delete($file->fs_path);

        $this->dispatch('file-deleted', $file->id);
        broadcast(new FileDeleted($file->share_id, $file->id));
    }
}

?>
<div class="p-6">
    @if ($this->file)
        <h3 class="text-xl">{{ $this->file->name }}</h3>
        <div>
            {{ __('Uploaded') }}: {{ $this->file->created_at?->diffForHumans() }} by {{ $this->file->uploader->name }}<br/>
            {{ __('Size') }}: {{ Number::fileSize($this->file->size ?? 0) }}
        </div>
        <div class="text-gray-500">
            {{ $this->file->description }}
        </div>
        <div class="my-6 flex">
            @php
                if ($this->share->public_token) {
                    $downloadUrl = route('publicShare.file.download', [
                        'shareId' => $this->share->id,
                        'publicToken' => $this->share->public_token,
                        'fileId' => $this->file->id,
                    ]);
                }
                else {
                    $downloadUrl = route('share.file.download', [
                        'shareId' => $this->share->id,
                        'fileId' => $this->file->id,
                    ]);
                }
            @endphp

            <div class="flex-grow">
                <a
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    href="{{ $downloadUrl }}"
                    target="_blank"
                >
                    {{ __('Download') }}
                </a>
            </div>
            @if (ShareAuthorization::hasSharePermission($this->share, 'write'))
                <x-danger-button
                    type="button"
                    wire:click.prevent="$dispatch('open-modal', 'file-delete')"
                >
                    {{ __('Delete…') }}
                </x-danger-button>
            @endif
        </div>

        <x-delete-modal name="file-delete" action="deleteFile">
            {{ __('Delete file') }}: {{ $this->file->name }}
        </x-delete-modal>
    @endif
</div>
