<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_service_attachments')->orderBy('id')->each(function (object $attachment): void {
            if (! Storage::disk('public')->exists($attachment->file_path)) {
                return;
            }

            $contents = Storage::disk('public')->get($attachment->file_path);
            if (Storage::disk('local')->put($attachment->file_path, $contents)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }

    public function down(): void
    {
        // Les pièces jointes restent privées lors d'un rollback applicatif.
    }
};
