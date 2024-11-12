<?php
declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use App\Models\Share;

new class extends Component {
    #[Locked]
    #[Reactive]
    public string $shareId;

    #[Computed]
    public function share(): ?Share
    {
        return Share::where('id', $this->shareId)->first();
    }

    #[Computed]
    public function files(): Collection
    {
        $share = $this->share();
        return $share
            ? $share->files()->orderBy('name')->get()
            : collect();
    }

    #[On('echo-private:shares.{shareId},FileCreated')]
    #[On('echo-private:shares.{shareId},FileDeleted')]
    #[On('echo-private:shares.{shareId},FileThumbnailReady')]
    public function handleEchoFileCreatedDeleted(): void
    {
        unset($this->files);
        unset($this->share);
    }

    public function selectFile(?string $fileId): void
    {
        $this->dispatch('file-select', fileId: $fileId);
    }

    #[On('file-created')]
    public function handleFileCreated(string $fileId): void
    {
        unset($this->share);
    }

    #[On('file-deleted')]
    public function handleFileDeleted(string $fileId): void
    {
        unset($this->share);
    }
}

?>
<div>
    <div class="flex flex-wrap gap-4 py-4 justify-center">
        @if ($this->files->isEmpty())
            No files have been uploaded yet!
        @endif

        @foreach ($this->files as $file)
            <a
                class="border-2 border-gray-500 rounded w-48 h-48 flex flex-col"
                href="#"
                wire:click.prevent="selectFile('{{$file->id}}')"
            >
                <div class="flex-grow flex justify-center align-middle">
                    @php
                        if ($file->webp_thumbnail) {
                            $url = 'data:image/webp;base64,' . base64_encode($file->webp_thumbnail);
                        } else {
                            $url = Vite::asset('resources/images/document.svg');
                        }
                    @endphp

                    <img class="w-[140px] h-[140px]" src="{{ $url }}" width="100" height="100" alt="Thumbnail of {{ $file->name }}"/>
                </div>
                <div class="text-center h-12 break-words">
                    {{ $file->name }}
                </div>
            </a>
        @endforeach
    </div>
</div>
