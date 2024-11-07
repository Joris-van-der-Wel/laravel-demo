<?php
declare(strict_types=1);

use App\Models\Share;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Facades\App\Services\ShareAuthorization;

new class extends Component {
    #[Locked]
    #[Reactive]
    public string $shareId;

    #[Computed]
    public function share(): Share
    {
        return Share::where('id', $this->shareId)->firstOrFail();
    }
}

?>
<div>
    @if (ShareAuthorization::hasSharePermission($this->share, 'owner'))
        <table class="w-full">
            <thead>
            <tr>
                <th class="py-2">Timestamp</th>
                <th>Action</th>
                <th>File</th>
                <th>User</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($this->share->auditLogs()->orderBy('timestamp')->with(['file', 'user'])->get() as $log)
                <tr>
                    <td class="py-2">
                        {{ $log->timestamp }}
                    </td>
                    <td>
                        {{ ucwords(str_replace('_', ' ', $log->type)) }}
                    </td>
                    <td>
                        {{ $log->file?->name }}
                    </td>
                    <td title="{{ $log->user?->name }}">
                        {{ $log->user?->email }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        No access
    @endif
</div>
