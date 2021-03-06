<?php


namespace App;


class Reservation
{


    protected $tickets, $customerEmail;

    public function __construct($tickets, $email)
    {
        $this->tickets = $tickets;
        $this->customerEmail = $email;
    }

    public function totalCost()
    {
        return $this->tickets->sum('price');
    }

    public function tickets()
    {
        return $this->tickets;
    }

    public function email()
    {
        return $this->customerEmail;
    }

    public function complete($paymentGateway, $paymentToken, $destinationAccountId)
    {
        $charge = $paymentGateway->charge($this->totalCost(), $paymentToken, $destinationAccountId);

        return Order::forTickets($this->tickets(), $this->email(), $charge);
    }


    public function cancel()
    {
        foreach ($this->tickets as $ticket) {
            $ticket->release();
        }
    }


}