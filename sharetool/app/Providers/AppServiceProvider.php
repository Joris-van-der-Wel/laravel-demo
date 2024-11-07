<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable implicit lazy loading by the ORM and enable a few strict checks
        Model::shouldBeStrict();

        // disable mime-type detection for user uploaded files, and always use binary
        $userUploadsFs = Storage::disk('user-uploads');
        $userUploadsFs->serveUsing(function (Request $request, $path, $headers) use ($userUploadsFs) {
            $headers += [
                'Content-Type' => 'application/octet-stream',
            ];
            return $userUploadsFs->response($path, null, $headers);
        });
    }
}
