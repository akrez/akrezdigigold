<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Scrap
 *
 * @property int $id
 * @property string $source
 * @property string $scrap_key
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|Page[] $pages
 * @property Collection|Product[] $products
 * @property Collection|Variant[] $variants
 */
class Scrap extends Model
{
    protected $table = 'scraps';

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $fillable = [
        'source',
        'scrap_key',
        'started_at',
        'completed_at',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
