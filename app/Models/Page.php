<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'scrap_id',
        'number',
        'completed_at',
    ];

    protected $casts = [
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
