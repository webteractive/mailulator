<?php

namespace Webteractive\Mailulator\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Webteractive\Mailulator\Mailulator;

/**
 * @property int $id
 * @property int $inbox_id
 * @property string $from
 * @property array $to
 * @property array|null $cc
 * @property array|null $bcc
 * @property string $subject
 * @property string|null $html_body
 * @property string|null $text_body
 * @property array $headers
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property-read Inbox $inbox
 * @property-read Collection<int, Attachment> $attachments
 */
class Email extends Model
{
    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        return Mailulator::connectionName();
    }

    public $timestamps = false;

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'headers' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('subject', 'like', $like)
                ->orWhere('from', 'like', $like)
                ->orWhere('to', 'like', $like);
        });
    }
}
