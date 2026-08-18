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
 * @property int $scrap_id
 * @property int $page_id
 * @property string $external_id
 * @property string $title
 * @property string|null $image_url
 * @property string|null $product_url
 * @property int|null $http_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Page $page
 * @property Scrap $scrap
 * @property Collection|Variant[] $variants
 */
class Product extends Model
{
    protected $table = 'products';

    protected $casts = [
        'scrap_id' => 'int',
        'page_id' => 'int',
    ];

    protected $fillable = [
        'scrap_id',
        'page_id',
        'external_id',
        'title',
        'image_url',
        'product_url',
        'http_status',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
