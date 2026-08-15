<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Product
 *
 * @property int $id
 * @property string $external_id
 * @property string $title
 * @property string|null $image_url
 * @property string|null $product_url
 * @property int $page_number
 * @property int $page_id
 * @property int $scrap_id
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Page $page
 * @property Scrap $scrap
 * @property Collection|Variant[] $variants
 */
class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'external_id',
        'title',
        'image_url',
        'product_url',
        'page_number',
        'page_id',
        'scrap_id',
        'completed_at',
    ];

    protected $casts = [
        'page_number' => 'int',
        'page_id' => 'int',
        'scrap_id' => 'int',
        'completed_at' => 'datetime',
    ];

    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
