<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Services\ShareAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ShareController extends Controller
{
    public function __construct(protected ShareAuthorization $shareAuthorization)
    {
    }

    private function redirectToFileUrl(Share $share, string $fileId): RedirectResponse
    {
        $file = $share->files()
            ->where('id', $fileId)
            ->firstOrFail();

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
        $share = $this->shareAuthorization->authorizeShare($shareId);
        return $this->redirectToFileUrl($share, $fileId);
    }

    // Careful: auth middleware is not enabled for this route
    public function downloadFilePublic(string $shareId, string $token, string $fileId): RedirectResponse
    {
        $share = $this->shareAuthorization->authorizeShare($shareId, publicToken: $token);
        return $this->redirectToFileUrl($share, $fileId);
    }
}
