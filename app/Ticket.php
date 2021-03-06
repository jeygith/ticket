<?php

namespace App;

use App\Facades\TicketCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Ticket
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket available()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Ticket query()
 * @mixin \Eloquent
 */
class Ticket extends Model
{

    protected $guarded = [];

    public function scopeAvailable($query)
    {
        return $query->whereNull('order_id')->whereNull('reserved_at');
    }

    public function scopeSold($query)
    {
        return $query->whereNotNull('order_id');
    }

    public function reserve()
    {

        $this->update(['reserved_at' => Carbon::now()]);

    }

    public function release()
    {
        $this->update(['reserved_at' => null]);

    }


    public function claimFor($order)
    {
        $this->code = TicketCode::generateFor($this);

        $order->tickets()->save($this);
    }


    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getPriceAttribute()
    {
        return $this->concert->ticket_price;
    }
}
