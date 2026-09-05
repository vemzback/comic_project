<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reading_history')
            ->select('user_id', 'comic_id', 'chapter_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('user_id', 'comic_id', 'chapter_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function ($group): void {
                $histories = DB::table('reading_history')
                    ->where('user_id', $group->user_id)
                    ->where('comic_id', $group->comic_id)
                    ->when(
                        $group->chapter_id === null,
                        fn ($query) => $query->whereNull('chapter_id'),
                        fn ($query) => $query->where('chapter_id', $group->chapter_id)
                    )
                    ->orderByDesc('last_read_at')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                $histories->skip(1)->each(
                    fn ($history) => DB::table('reading_history')->where('id', $history->id)->delete()
                );
            });

        Schema::table('reading_history', function (Blueprint $table) {
            // Keep this as a regular column instead of a generated column.
            // MySQL rejects generated columns whose source foreign key uses
            // ON DELETE SET NULL, which is required by chapter_id.
            $table->unsignedBigInteger('chapter_identity')->default(0);
        });

        DB::table('reading_history')
            ->whereNotNull('chapter_id')
            ->update(['chapter_identity' => DB::raw('chapter_id')]);

        Schema::table('reading_history', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'comic_id', 'chapter_identity'],
                'reading_history_identity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reading_history', function (Blueprint $table) {
            $table->dropUnique('reading_history_identity_unique');
            $table->dropColumn('chapter_identity');
        });
    }
};
