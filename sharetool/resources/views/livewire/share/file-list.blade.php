<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
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
    public function share(): Share
    {
        return Share::where('id', $this->shareId)->firstOrFail();
    }

    #[Computed]
    public function files(): Collection
    {
        return $this->share()->files()->orderBy('name')->get();
    }

    #[On('echo-private:shares.{shareId},FileCreated')]
    #[On('echo-private:shares.{shareId},FileDeleted')]
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
                    <img class="w-auto h-auto" src="{{ Vite::asset('resources/images/document.svg') }}" width="100" height="100"/>
                </div>
                <div class="text-center h-12">
                    {{ $file->name }}
                </div>
            </a>
        @endforeach
    </div>
</div>
