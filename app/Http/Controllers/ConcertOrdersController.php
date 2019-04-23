<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Reservation;
use Illuminate\Http\Request;

class ConcertOrdersController extends Controller
{
    //

    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertId)
    {
        $concert = Concert::published()->findorFail($concertId);

        $this->validate(request(), [
            'email' => 'required|email',
            'ticket_quantity' => ['required', 'integer', 'min:1'],
            'payment_token' => ['required']
        ]);

        try {
            // find tickets

            $reservation = $concert->reserveTickets(request('ticket_quantity'), request('email'));


            // charge customers create order for those tickets

            $order = $reservation->complete($this->paymentGateway, request('payment_token'));


            return response()->json($order, 201);
        } catch (PaymentFailedException $e) {
            $reservation->cancel();
            return response()->json([], 422);

        } catch (NotEnoughTicketsException $e) {
            return response()->json([], 422);
        }
    }
}
