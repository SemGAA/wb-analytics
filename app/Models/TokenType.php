<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenType extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function apiServices(): BelongsToMany
    {
        return $this->belongsToMany(ApiService::class, 'api_service_token_types')->withTimestamps();
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }
}
