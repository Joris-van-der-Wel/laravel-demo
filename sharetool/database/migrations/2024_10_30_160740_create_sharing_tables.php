<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('name');
            $table->text('description');
            $table->string('public_token', 64)->nullable();
            $table->string('password')->nullable();
        });

        Schema::create('share_user_access', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignUlid('share_id')->constrained('shares');
            $table->foreignId('user_id')->constrained('users');
            $table->unique(['user_id', 'share_id'], 'user_share_unique');
            $table->enum('permission', ['read', 'write']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUlid('share_id')->constrained('shares');
            $table->foreignId('uploader_id')->constrained('users');
            $table->string('fs_path');
            $table->string('name');
            $table->text('description');
            $table->unsignedInteger('size');
            $table->text('webp_thumbnail')->charset('binary')->nullable();
        });

        Schema::create('share_audit_logs', function (Blueprint $table) {
            $table->id()->primary();
            $table->timestamp('timestamp')->useCurrent();
            $table->foreignUlid('share_id')->constrained('shares');
            $table->foreignUlid('file_id')->nullable()->constrained('files');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->enum('type', [
                'share_create',
                'share_update',
                'share_delete',
                'share_access_change',
                'file_create',
                'file_update',
                'file_delete',
                'file_download',
            ]);
            $table->jsonb('details');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
        Schema::dropIfExists('share_user_access');
        Schema::dropIfExists('files');
        Schema::dropIfExists('share_audit_logs');
    }
};
