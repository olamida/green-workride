<?php

namespace App\Models;

use App\Enums\EmployerJoinVia;
use App\Enums\EmployerMemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'user_id',
        'employee_id',
        'joined_via',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmployerMemberStatus::class,
            'joined_via' => EmployerJoinVia::class,
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === EmployerMemberStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === EmployerMemberStatus::Pending;
    }
}
