<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'photographer_id',
        'name',
        'description',
        'price',
        'photo_count',
        'includes_digital',
        'includes_prints',
        'features',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'includes_digital' => 'boolean',
            'includes_prints' => 'boolean',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the photographer that owns the package.
     */
    public function photographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    /**
     * Get the orders that use this package.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
