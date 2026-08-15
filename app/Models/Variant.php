<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variant extends Model
{
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
        'size' => 'decimal:3',
        'price' => 'integer',
        'price_per_gram' => 'decimal:2',
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
