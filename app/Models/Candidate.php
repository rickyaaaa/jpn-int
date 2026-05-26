<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'name',
    ];

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }
}
