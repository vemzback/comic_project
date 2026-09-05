<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    use HasFactory;

    protected $table = 'reading_history';

    protected $fillable = [
        'user_id',
        'comic_id',
        'chapter_id',
        'page_number',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'page_number' => 'integer',
        'chapter_identity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ReadingHistory $history): void {
            $history->chapter_identity = $history->chapter_id ?? 0;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
