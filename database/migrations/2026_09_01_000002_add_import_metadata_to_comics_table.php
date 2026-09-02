<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->string('external_provider', 32)->nullable()->after('id');
            $table->string('external_id', 120)->nullable()->after('external_provider');
            $table->text('source_url')->nullable()->after('external_id');
            $table->string('author')->nullable()->after('description');
            $table->string('publisher')->nullable()->after('author');
            $table->date('original_published_at')->nullable()->after('publisher');
            $table->json('source_metadata')->nullable()->after('seo_description');

            $table->unique(['external_provider', 'external_id'], 'comics_external_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->dropUnique('comics_external_source_unique');
            $table->dropColumn([
                'external_provider',
                'external_id',
                'source_url',
                'author',
                'publisher',
                'original_published_at',
                'source_metadata',
            ]);
        });
    }
};
