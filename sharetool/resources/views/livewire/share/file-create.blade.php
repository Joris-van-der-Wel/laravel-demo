<?php
declare(strict_types=1);

use App\Events\FileCreated;
use App\Models\File;
use App\Models\Share;
use App\Jobs\MakeFileThumbnail;
use Facades\App\Services\ShareAuthorization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

const DISK = 'user-uploads';

new class extends Component {
    use WithFileUploads;

    #[Locked]
    #[Reactive]
    public string $shareId;

    #[Validate('file')]
    public ?UploadedFile $file = null;

    #[Validate('required', 'string')]
    public string $description = '';

    #[Computed]
    public function share(): Share
    {
        return Share::where('id', $this->shareId)->firstOrFail();
    }

    public function save(): void
    {
        $this->validate();
        $share = $this->share();

        if (!ShareAuthorization::hasSharePermission($share, 'write')) {
            return;
        }

        $file = new File;
        $file->id = Str::ulid()->toBase32();
        $file->share_id = $share->id;
        $file->uploader_id = auth()->user()->id;
        $file->description = $this->description;
        $file->name = $this->file->getClientOriginalName();
        $file->fs_path = "$share->id/$file->id/$file->name";

        $path = $this->file->storeAs("$share->id/$file->id", $file->name, [
            'disk' => DISK,
            'visibility' => 'private',
        ]);
        $file->size = Storage::disk(DISK)->size($path);

        // The disk I/O operations should be outside of the transaction
        DB::transaction(function () use ($path, $file) {
            $file->save();
            $file->addAuditLog('file_create');
        });
        # todo add thumbnail generation to queue if it's an image

        $this->dispatch('file-created', $file->id);
        broadcast(new FileCreated($file->share_id, $file->id));
        MakeFileThumbnail::dispatch($file);
    }
}

?>
<div>
    <form wire:submit="save">
        <div class="my-2">
            <x-input-label for="file" value="File"/>
            <input type="file" wire:model="file">
            <x-input-error :messages="$errors->get('file')" class="mt-2"/>
        </div>

        <div class="my-2">
            <x-input-label for="description" value="Description"/>
            <x-text-input wire:model="description" id="description" class="block mt-1 w-full" type="text" name="description" required/>
            <x-input-error :messages="$errors->get('description')" class="mt-2"/>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-4">
                Upload
            </x-primary-button>
        </div>
    </form>
</div>
