<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'exp_month'  => 'integer',
        'exp_year'   => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
