<?php

namespace App\Models;

use Database\Factories\EolAlertDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EolAlertDelivery extends Model
{
    /** @use HasFactory<EolAlertDeliveryFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'version_id',
        'eol_date',
        'window_days',
        'dispatched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eol_date' => 'date',
            'window_days' => 'integer',
            'dispatched_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
