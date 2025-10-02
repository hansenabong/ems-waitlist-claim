<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class EventWaitlist
 *
 * Purpose:
 * - Represents a single user’s position on an event’s waitlist.
 * - Tracks notification/claim lifecycle for “offer window” logic.
 *
 * Business rules:
 * - A user can appear at most once per event (DB unique constraint).
 * - When a seat is offered, notified_at/hold_expires_at are set.
 * - When claimed, claimed_at is set and the booking is created.
 * - Soft deletes let users rejoin later without losing audit history.
 */

class EventWaitlist extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['event_id', 'user_id', 'notified_at', 'hold_expires_at', 'claimed_at'];
    protected $casts = [
        'notified_at' => 'datetime',
        'hold_expires_at' => 'datetime',
        'claimed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
