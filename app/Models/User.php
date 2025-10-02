<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
/**
 * Class User
 *
 * Purpose:
 *  - Application user (attendee or organiser) used for authentication/authorisation.
 *
 * Security/PII:
 *  - Contains name,email and password
 *  - password is hashed (see casts()); password + remember_token are hidden for serialization.
 *
 * Domain notes:
 *  - A 'type' column (e.g., 'attendee' | 'organiser') is used by Gates/Policies at AppServiceProvider.
 *    Ensure this field exists in the migration/seeders even if not mass assignable here.
 *
 * Relations:
 *  - events():   events this user organises (organiser role).
 *  - bookings(): bookings this user has made (attendee role).
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }
    // Foreign key on events.organiser_id
    public function events()
    {        
        return $this->hasMany(Event::class, 'organiser_id');
    }
    //Bookings this user has made (attendee role).
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function waitlist()
    {
        return $this->hasMany(EventWaitlist::class);
    }
}
