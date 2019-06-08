<?php

namespace App;

use App\Facades\OrderConfirmationNumber;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Order
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Ticket[] $tickets
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order query()
 * @mixin \Eloquent
 */
class Order extends Model
{
    //

    protected $guarded = [];

    public static function forTickets($tickets, $email, $charge)
    {

        $order = self::create([
            // 'concert_id' => $this->id,
            'email' => $email,
            'amount' => $charge->amount(),
            'confirmation_number' => OrderConfirmationNumber::generate(),
            'card_last_four' => $charge->cardLastFour(),
        ]);


        //  $order->tickets()->saveMany($tickets);

        $tickets->each->claimFor($order);

        return $order;

    }

    public static function findByConfirmationNumber($confirmationNumber)
    {
        return self::where('confirmation_number', $confirmationNumber)->firstOrFail();
    }


    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }


    /* public function cancel()
     {
         foreach ($this->tickets as $ticket) {
             $ticket->release();
         }

         $this->delete();
     }*/

    /**
     * @return int
     */
    public function ticketQuantity()
    {
        return $this->tickets()->count();
    }


    public function toArray()
    {
        return [
            'confirmation_number' => $this->confirmation_number,
            'email' => $this->email,
            'amount' => $this->amount,
            'tickets' => $this->tickets->map(function ($ticket) {
                return ['code' => $ticket->code];
            })->all(),
        ];
    }


    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount / 100, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('F d Y, g:ia');
    }

}
