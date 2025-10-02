<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * Class Event
 *
 * Purpose:
 *  - Organiser-created event that users can book.
 *
 * Domain:
 *  - organiser_id references users.id (the organiser account).
 *  - capacity bounds attendance; controllers should enforce capacity checks.
 *  - starts_at is a future datetime and is cast to Carbon for convenience.
 *
 * Performance:
 *  - Use with('organiser') and withCount('bookings') in queries to avoid N+1 and
 *    to compute availability efficiently for lists (index/dashboard).
 *
 * Relations:
 *  - organiser(): the User who owns/created the event.
 *  - bookings():  all bookings made for this event.
 */
class Event extends Model
{
    use HasFactory;   //this enables Event::factory()
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['organiser_id', 'title', 'description', 'starts_at', 'location', 'capacity'];
    /**
     * Attribute casting.
     * starts_at -> Carbon instance for date math/formatting. */
    protected $casts = ['starts_at' => 'datetime'];
    //The organiser (User) who owns this event.
    public function organiser()
    {
        return $this->belongsTo(User::class, 'organiser_id');
    }
    //All bookings made for this event.
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function waitlist()
    {
        return $this->hasMany(EventWaitlist::class);
    }
}
