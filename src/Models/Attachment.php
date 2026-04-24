<?php

namespace Webteractive\Mailulator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $email_id
 * @property string $filename
 * @property string $mime_type
 * @property int $size
 * @property string $disk
 * @property string $path
 * @property Carbon|null $created_at
 * @property-read Email $email
 */
class Attachment extends Model
{
    protected $connection = 'mailulator';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }
}
