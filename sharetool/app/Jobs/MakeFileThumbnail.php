<?php

namespace App\Jobs;

use App\Events\FileThumbnailReady;
use App\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;

class MakeFileThumbnail implements ShouldQueue
{
    use Queueable;

    const supportedMimeTypes = [
        'image/gif' => true,
        'image/jpeg' => true,
        'image/png' => true,
        'image/bmp' => true,
        'image/avif' => true,
        'image/webp' => true,
    ];
    const maxImageSize = 10 * 1024 * 1024; // 10 MiB
    const width = 100;
    const height = 100;
    const outputQuality = 90;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public readonly File $file,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $file = $this->file;
        $mimeType = Storage::disk('user-uploads')->mimeType($file->fs_path);
        $mimeTypeSupported = self::supportedMimeTypes[$mimeType] ?? false;

        if (!$mimeTypeSupported || $file->size > self::maxImageSize) {
            return;
        }

        $manager = new ImageManager(
            new Driver,
            autoOrientation: false,
            decodeAnimation: false,
        );
        $image = $manager->read(Storage::disk('user-uploads')->get($file->fs_path));
        $image->setColorspace(RgbColorspace::class);

        // pad ensures the output dimension is exactly 140x140, scaling down and adding
        // padding as needed.
        // This makes it easier to place on the page
        $image->pad(self::width, self::height, 'ffffff00', 'center');

        $encoded = $image->toWebp(self::outputQuality);
        $file->webp_thumbnail = $encoded;
        $file->save();

        broadcast(new FileThumbnailReady($file->share_id, $file->id));
    }
}
