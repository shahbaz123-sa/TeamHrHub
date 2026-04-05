<?php

namespace App\Models;

use Modules\HRM\Models\Employee;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\HRM\Traits\User\RoleConditions;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasPermissions;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, HasPermissions, SoftDeletes, RoleConditions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'plan',
        'status',
        'billing_email',
        'company',
        'tax_id',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['avatar_url'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the available roles for the user.
     *
     * @return array<string, string>
     */
    public static function getAvailableRoles(): array
    {
        return [
            'admin' => 'Admin',
            'author' => 'Author',
            'editor' => 'Editor',
            'maintainer' => 'Maintainer',
            'subscriber' => 'Subscriber',
        ];
    }

    /**
     * Get the available plans for the user.
     *
     * @return array<string, string>
     */
    public static function getAvailablePlans(): array
    {
        return [
            'basic' => 'Basic',
            'company' => 'Company',
            'enterprise' => 'Enterprise',
            'team' => 'Team',
        ];
    }

    /**
     * Get the available statuses for the user.
     *
     * @return array<string, string>
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the avatar URL from S3
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::disk('s3')->url($this->avatar) : null;
    }

    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class);
    }
}
