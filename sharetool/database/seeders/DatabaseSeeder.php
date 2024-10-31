<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\File;
use App\Models\Share;
use App\Models\ShareAuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data for testing.
     */
    public function run(): void
    {
        $userUploadsFs = Storage::disk('user-uploads');
        $fileSequence = [];

        for ($i = 0; $i <= 9; $i++) {
            $source_path = __DIR__ . "/../../resources/images/background/$i.jpg";
            $handle = fopen($source_path, 'r');
            try {
                $fs_path = "fake/$i.jpg";
                $userUploadsFs->writeStream($fs_path, $handle);
                $fileSequence[] = [
                    'fs_path' => $fs_path,
                    'size' => filesize($source_path),
                ];
            }
            finally {
                fclose($handle);
            }
        }

        DB::transaction(function () use ($fileSequence) {
            $users_count = 3;
            $users = User::factory($users_count)
                ->sequence(
                    ['email' => 'user1@localhost'],
                    ['email' => 'user2@localhost'],
                    ['email' => 'user3@localhost'],
                )
                ->create();

            $shares = Share::factory(10)
                ->recycle($users)
                ->hasAuditLogs(1, function (array $attributes, Share $share) {
                    return [
                        'user_id' => $share->owner_id,
                        'type' => 'share_create',
                    ];
                })
                ->create();

            foreach ($shares as $share) {
                $i = 0;

                do {
                    $user = $users[rand(0, $users_count - 1)];

                    if (++$i > 100) {
                        // protect against infinite loops
                        break 2;
                    }
                }
                // do not grant the owner explicit access, because the owner always has full access.
                while ($user->id === $share->owner_id);

                $permission = ['read', 'write'][rand(0, 1)];

                $share
                    ->userAccess()
                    ->attach($user, ['permission' => $permission]);

                ShareAuditLog::factory(1)
                    ->create([
                        'type' => 'share_access_change',
                        'share_id' => $share,
                        'user_id' => $share->owner_id,
                    ]);
            }

            File::factory(100)
                ->recycle($shares)
                ->recycle($users)
                ->sequence(...$fileSequence)
                ->hasAuditLogs(1, function (array $attributes, File $file) {
                    return [
                        'share_id' => $file->share_id,
                        'user_id' => $file->uploader_id,
                        'type' => 'file_create',
                    ];
                })
                ->hasAuditLogs(3, function (array $attributes, File $file) use ($users, $users_count) {
                    return [
                        'share_id' => $file->share_id,
                        'user_id' => $file->share->public_token === null
                            ? $users[rand(0, $users_count - 1)]
                            : null,
                        'type' => 'file_download',
                    ];
                })
                ->create();
        });
    }
}
