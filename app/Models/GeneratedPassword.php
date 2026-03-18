<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedPassword extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'hash',
        'length',
        'options',
        'created_at',
    ];

    protected $casts = [
        'options' => 'array',
        'created_at' => 'datetime',
    ];
}
