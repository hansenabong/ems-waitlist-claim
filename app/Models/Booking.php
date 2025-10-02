<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * Class Booking
 *
 * Purpose:
 *  - Represents a user's reservation for a specific Event.
 *
 * Data integrity:
 *  - DB should enforce UNIQUE(event_id, user_id) to prevent duplicates.
 *  - On write paths, controllers should also check capacity and duplicates
 *    (defense in depth with a DB unique index).
 *
 *
 * Relations:
 *  - user():    belongs to the booking user.
 *  - event():   belongs to the booked event.
 */
class Booking extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['user_id', 'event_id'];
    
    //owner user of this booking
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //Event that this booking is for.
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
