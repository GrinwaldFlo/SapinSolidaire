<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    public const VISITOR = 'visitor';
    public const VALIDATOR = 'validator';
    public const ORGANIZER = 'organizer';
    public const RECEPTION = 'reception';
    public const ADMIN = 'admin';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get users with this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
