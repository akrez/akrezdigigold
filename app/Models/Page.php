<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Page
 *
 * @property int $id
 * @property int $number
 * @property int $scrap_id
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Scrap $scrap
 * @property Collection|Product[] $products
 */
class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'scrap_id',
        'number',
        'completed_at',
    ];

    protected $casts = [
        'number' => 'int',
        'scrap_id' => 'int',
        'completed_at' => 'datetime',
    ];

    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
