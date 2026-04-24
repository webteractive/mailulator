<?php

namespace Webteractive\Mailulator\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $api_key
 * @property int|null $retention_days
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $unread_count
 * @property-read Collection<int, Email> $emails
 */
class Inbox extends Model
{
    protected $connection = 'mailulator';

    protected $guarded = [];

    protected $hidden = ['api_key'];

    protected $casts = [
        'last_used_at' => 'datetime',
        'retention_days' => 'integer',
        'settings' => 'array',
    ];

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function scopeForToken($query, string $plaintext)
    {
        return $query->where('api_key', static::hashToken($plaintext));
    }

    public static function hashToken(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
