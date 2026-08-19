<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Page
 *
 * @property int $id
 * @property int $scrap_id
 * @property int $number
 * @property int|null $http_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Scrap $scrap
 * @property Collection|Product[] $products
 */
class Page extends Model
{
    protected $table = 'pages';

    protected $casts = [
        'scrap_id' => 'int',
        'number' => 'int',
    ];

    protected $fillable = [
        'scrap_id',
        'number',
        'http_status',
    ];

    public function scopeNotPending(Builder $query, int $scrapId)
    {
        return $query->where('scrap_id', $scrapId)
            ->whereNot(function ($q) {
                $q->whereNull('http_status')
                    ->orWhereIn('http_status', [0, 429]);
            });
    }

    public function scopePending(Builder $query, int $scrapId)
    {
        return $query->where('scrap_id', $scrapId)
            ->where(function ($q) {
                $q->whereNull('http_status')
                    ->orWhereIn('http_status', [0, 429]);
            });
    }

    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
