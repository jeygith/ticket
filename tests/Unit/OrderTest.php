<?php

namespace Tests\Unit;

use App\Billing\Charge;
use App\Concert;
use App\Order;
use App\Reservation;
use App\Ticket;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{

    use DatabaseMigrations;


    /** @test */
    function creating_an_order_from_tickets_email_and_charge()
    {

        $tickets = factory(Ticket::class, 3)->create();

        $charge = new Charge(['amount' => 3600, 'card_last_four' => '1234']);

        $order = Order::forTickets($tickets, 'john@example.com', $charge);


        $this->assertEquals('john@example.com', $order->email);

        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals(1234, $order->card_last_four);


    }

    /** @test */
    function retrieving_an_order_by_confirmation_number()
    {
        $order = factory(Order::class)->create([
            'confirmation_number' => 'ORDERCONFIRMATION1234'
        ]);

        $foundOrder = Order::findByConfirmationNumber('ORDERCONFIRMATION1234');


        $this->assertEquals($order->id, $foundOrder->id);
    }

    /** @test */
    function retrieving_a_nonexistent_order_by_confirmation_number_throws_an_exception()
    {

        try {
            Order::findByConfirmationNumber('NONEXISTENT');
        } catch (ModelNotFoundException $e) {
            self::assertEquals(1, 1);
            return;
        }

        $this->fail('No matching order was found for the specified confirmation number, but an exception was not thrown');
    }

    /** @test */
    function converting_to_an_array()
    {


        $order = factory(Order::class)->create([
            'confirmation_number' => 'ORDERCONFIRMATION1234',
            'email' => 'jane@example.com',
            'amount' => 6000
        ]);

        $order->tickets()->saveMany(factory(Ticket::class)->times(5)->create());


        $result = $order->toArray();

        $this->assertEquals([
            'confirmation_number' => 'ORDERCONFIRMATION1234',
            'email' => 'jane@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000
        ], $result);
    }


    /** @test */
    /*    function tickets_are_released_when_order_is_cancelled()
        {
            $concert = factory(Concert::class)->create()->addTickets(10);

            $order = $concert->orderTickets('jane@example.com', 5);


            $this->assertEquals(5, $concert->ticketsRemaining());


            $order->cancel();


            $this->assertEquals(10, $concert->ticketsRemaining());

            $this->assertNull(Order::find($order->id));


        }*/
}
