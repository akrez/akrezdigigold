<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Variant
 * 
 * @property int $id
 * @property int $product_id
 * @property string $seller
 * @property string $carat
 * @property float $size
 * @property int $price
 * @property float $price_per_gram
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Product $product
 *
 * @package App\Models
 */
class Variant extends Model
{
	protected $table = 'variants';

	protected $fillable = [
		'product_id',
		'seller',
		'carat',
		'size',
		'price',
        'scrap_id',
        'price_per_gram',
	];

	protected $casts = [
		'product_id' => 'int',
		'size' => 'float',
		'price' => 'int',
		'price_per_gram' => 'float'
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
