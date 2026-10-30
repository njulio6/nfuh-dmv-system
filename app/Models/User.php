<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Standard self-service permissions granted to members by default.
     * Members do not need the 'member' role assigned to access these.
     * The 'member' role is assigned only when an administrator wants to enforce restrictions.
     */
    public static array $defaultMemberPermissions = [
        'attendance.rsvp',
        'attendance.view_own',
        'loans.apply',
        'loans.view_own',
        'savings.deposit_request',
        'savings.withdraw_request',
        'savings.view_own',
        'njangi.submit_payment',
        'njangi.view_own',
    ];

    /**
     * Determine if the user has the given permission.
     *
     * Architectural Access Policy:
     * 1. Super administrators ('admin') possess full authority across all modules.
     * 2. Direct permissions or permissions via assigned roles (e.g. Treasurer, Loan Committee) are checked.
     * 3. If the user HAS the 'member' role assigned: their member access is strictly governed by
     *    the permissions granted to that role (enabling admins to restrict specific capabilities).
     * 4. If the user DOES NOT have the 'member' role assigned: they automatically have all general member access.
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $permName = is_string($permission) ? $permission : ($permission->name ?? null);

        // 1. Super admin always has unrestricted access
        if ($this->hasRole('admin')) {
            return true;
        }

        // 2. Standard Spatie check (direct permissions or via assigned roles)
        try {
            $perm = $this->filterPermission($permission, $guardName);
            if ($this->hasDirectPermission($perm) || $this->hasPermissionViaRole($perm)) {
                return true;
            }
        } catch (\Throwable $e) {
            // Permission does not exist in DB or guard mismatch; continue evaluation
        }

        // 3. If 'member' role is explicitly assigned to this user and has configured permissions,
        // restrictions are enforced: Do NOT grant implicit default access (they only get what the role or direct assignment gave them).
        if ($this->hasRole('member')) {
            try {
                $memberRole = \Spatie\Permission\Models\Role::findByName('member', $guardName ?: 'web');
                if ($memberRole && $memberRole->permissions()->exists()) {
                    return false;
                }
            } catch (\Throwable $e) {
                // Continue to default member permissions fallback if role has no permissions configured
            }
        }

        // 4. Default policy: If 'member' role is NOT assigned, the user has all general member self-service permissions
        if ($permName && in_array($permName, self::$defaultMemberPermissions, true)) {
            return true;
        }

        return false;
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user's primary officer / executive role name for display.
     * Excludes the generic 'member' role so dual-role users show their true executive duty.
     */
    public function getOfficerRoleNameAttribute(): string
    {
        // 1. Check Spatie roles excluding generic 'member'
        $officerRole = $this->roles->first(fn($r) => strtolower($r->name) !== 'member');
        if ($officerRole) {
            return $officerRole->name === 'admin' ? 'Administrator' : $officerRole->name;
        }

        // 2. Check Member model roles
        $member = \App\Support\MemberResolver::fromUser($this);
        if ($member && $member->roles->isNotEmpty()) {
            return $member->roles->first()->name;
        }

        // 3. Fallbacks
        if ($this->hasRole('admin')) {
            return 'Administrator';
        }

        return 'Executive Officer';
    }
    /**
     * The attributes that are mass assignable.
     */
  protected $fillable = [
    'name',
    'email',
    'password',
    'organization_id',
];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}