<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'description',
        'website',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'industry',
        'size',
    ];

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }
}