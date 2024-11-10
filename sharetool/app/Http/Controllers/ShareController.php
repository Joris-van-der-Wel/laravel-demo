<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ShareController extends Controller
{
    private function redirectToFileUrl(File $file): RedirectResponse
    {
        $url = Storage::disk('user-uploads')->temporaryUrl(
            $file->fs_path,
            now()->addMinutes(5),
            // todo this only works for s3
            // [
            //     'ResponseContentType' => 'application/octet-stream',
            //     'ResponseContentDisposition' => "attachment; filename=$file->name", // todo proper escaping of file name
            // ]
        );

        $file->addAuditLog('file_download');

        return redirect($url);
    }

    public function downloadFile(string $shareId, string $fileId): RedirectResponse
    {
        $file = File::where('id', $fileId)
            ->where('share_id', $shareId)
            ->firstOrFail();

        Gate::authorize('view', $file);

        return $this->redirectToFileUrl($file);
    }

    // Careful: auth middleware is not enabled for this route
    public function downloadFilePublic(string $shareId, string $publicToken, string $fileId): RedirectResponse
    {
        $file = File::where('id', $fileId)
            ->where('share_id', $shareId)
            ->firstOrFail();

        Gate::authorize('view', [$file, $publicToken]);

        return $this->redirectToFileUrl($file);
    }
}
