<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Variant
 *
 * @property int $id
 * @property int $scrap_id
 * @property int $product_id
 * @property string $seller
 * @property string $external_id
 * @property string $carat
 * @property float $size
 * @property int $price
 * @property int $price_per_gram
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Product $product
 * @property Scrap $scrap
 */
class Variant extends Model
{
    protected $table = 'variants';

    protected $casts = [
        'scrap_id' => 'int',
        'product_id' => 'int',
        'size' => 'float',
        'price' => 'int',
        'price_per_gram' => 'int',
    ];

    protected $fillable = [
        'scrap_id',
        'product_id',
        'seller',
        'external_id',
        'carat',
        'size',
        'price',
        'price_per_gram',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }
}
