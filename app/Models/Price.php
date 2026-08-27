<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Price
 *
 * @property int $id
 * @property string $source
 * @property string $price_key
 * @property string $carat
 * @property int $price
 * @property Carbon $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Price extends Model
{
    protected $table = 'prices';

    protected $casts = [
        'price' => 'int',
        'fetched_at' => 'datetime',
    ];

    protected $fillable = [
        'source',
        'price_key',
        'carat',
        'price',
        'fetched_at',
    ];
}
