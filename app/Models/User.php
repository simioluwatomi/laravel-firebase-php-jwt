<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Enums\TwoFactorAuthenticationMethod;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int                                                       $id
 * @property string                                                    $name
 * @property string                                                    $email
 * @property null|Carbon                                               $email_verified_at
 * @property string                                                    $password
 * @property null|string                                               $remember_token
 * @property null|Carbon                                               $created_at
 * @property null|Carbon                                               $updated_at
 * @property null|mixed                                                $two_factor_secret
 * @property null|array<array-key, mixed>                              $two_factor_recovery_codes
 * @property null|Carbon                                               $two_factor_disabled_at
 * @property TwoFactorAuthenticationMethod                             $two_factor_method
 * @property DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property null|int                                                  $notifications_count
 *
 * @method static \Database\Factories\UserFactory                    factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorDisabledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_disabled_at',
        'two_factor_method',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function hasTwoFactorAuthenticationEnabled(): bool
    {
        return $this->two_factor_disabled_at === null;
    }

    public function usesTwoFactorAuthApp(): bool
    {
        return $this->two_factor_method === TwoFactorAuthenticationMethod::AUTHENTICATOR_APP;
    }

    public function usesTwoFactorEmail(): bool
    {
        return $this->two_factor_method === TwoFactorAuthenticationMethod::EMAIL;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_disabled_at' => 'datetime',
            'two_factor_method' => TwoFactorAuthenticationMethod::class,
            'two_factor_recovery_codes' => 'array',
            'two_factor_secret' => 'encrypted',
        ];
    }
}
