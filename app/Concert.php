<?php

namespace App;

use App\Exceptions\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Model;
use Storage;

/**
 * App\Concert
 *
 * @property-read mixed $formatted_date
 * @property-read mixed $formatted_start_time
 * @property-read mixed $ticket_price_in_dollars
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Ticket[] $tickets
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Concert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Concert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Concert published()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Concert query()
 * @mixin \Eloquent
 */
class Concert extends Model
{
    //
    protected $guarded = [];

    protected $dates = ['date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendeeMessage()
    {
        return $this->hasMany(AttendeeMessage::class);
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('F j, Y');
    }


    public function getFormattedStartTimeAttribute()
    {
        return $this->date->format('g:ia');
    }

    public function getTicketPriceInDollarsAttribute()
    {
        return number_format($this->ticket_price / 100, 2);
    }


    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function isPublished()
    {
        return $this->published_at !== null;
    }

    public function publish()
    {
        $this->update(['published_at' => $this->freshTimestamp()]);

        $this->addTickets($this->ticket_quantity);
    }

    public function orders()
    {
        // return $this->belongsToMany(Order::class, 'tickets');
        return Order::whereIn('id', $this->tickets()->pluck('order_id'));

    }


    public function hasOrderFor($email)
    {
        return $this->orders()->where('email', $email)->count() > 0;

    }

    public function ordersFor($email)
    {
        return $this->orders()->where('email', $email)->get();

    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }


    /*    public function orderTickets($email, $ticketQuantity)
        {
            $tickets = $this->findTickets($ticketQuantity);


            return $this->createOrder($email, $tickets);

        }*/

    public function reserveTickets($quantity, $email)
    {
        $tickets = $this->findTickets($quantity)->each(function ($ticket) {
            $ticket->reserve();
        });


        return new Reservation($tickets, $email);

    }


    public function findTickets($quantity)
    {
        $tickets = $this->tickets()->available()->take($quantity)->get();


        if ($tickets->count() < $quantity) {
            throw new NotEnoughTicketsException;
        }

        return $tickets;

    }

    /*    public function createOrder($email, $tickets)
        {

            return Order::forTickets($tickets, $email, $tickets->sum('price'));


        }*/

    public function addTickets($quantity)
    {
        foreach (range(1, $quantity) as $i) {
            $this->tickets()->create([]);
        }

        return $this;

    }

    /**
     *
     */
    public function ticketsRemaining()
    {
        return $this->tickets()->available()->count();
    }

    public function ticketsSold()
    {

        // return $this->tickets()->whereNotNull('order_id')->count();
        return $this->tickets()->sold()->count();
    }

    public function totalTickets()
    {
        return $this->tickets->count();
    }

    public function percentSoldOut()
    {
        return number_format($this->ticketsSold() / $this->totalTickets() * 100, 2);
    }

    public function revenueInDollars()
    {
        return $this->orders()->sum('amount') / 100;
    }

    public function hasPoster()
    {
        return $this->poster_image_path !== null;
    }

    public function posterUrl()
    {
        return Storage::disk('public')->url($this->poster_image_path);
    }
}
