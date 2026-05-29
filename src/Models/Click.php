<?php

declare(strict_types=1);

namespace Obelaw\Ium\Url\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Obelaw\Ium\Url\Base\ModelBase;

class Click extends ModelBase
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'link_id',
        'ip_address',
        'user_agent',
        'device_type',
        'referrer',
    ];

    /**
     * Link relation.
     *
     * @return BelongsTo
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
