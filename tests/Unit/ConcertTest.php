<?php

namespace Tests\Unit;

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function can_get_formatted_date()
    {
        // create concert with a known date
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 8.00pm')
        ]);

        // retrieve the formatted date

        // verify date is formatted as expected

        $this->assertEquals('December 1, 2016', $concert->formatted_date);
    }

    /** @test */

    function can_get_formatted_start_time()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 17:00:00')
        ]);

        $this->assertEquals('5:00pm', $concert->formatted_start_time);
    }

    /**
     * @test
     */
    function can_get_ticket_price_in_dollars()
    {
        $concert = factory(Concert::class)->make([
            'ticket_price' => 6750
        ]);


        $this->assertEquals('67.50', $concert->ticket_price_in_dollars);
    }

    /** @test */
    function concerts_with_a_published_at_date_are_published()
    {
        //test query scope
        $publishedConcertA = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-1 week')
        ]);
        $publishedConcertB = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-1 week')
        ]);
        $unpublishedConcerts = factory(Concert::class)->create([
            'published_at' => null]);


        $publishedConcerts = Concert::published()->get();

        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcerts));

    }

    /** @test */
    function concerts_can_be_published()
    {
        $concert = factory(Concert::class)->create([
            'published_at' => null,
            'ticket_quantity' => 5
        ]);

        $this->assertFalse($concert->isPublished());

        $this->assertEquals(0, $concert->ticketsRemaining());


        $concert->publish();

        $this->assertTrue($concert->isPublished());

        $this->assertEquals(5, $concert->ticketsRemaining());

    }


    /** @test */
    function can_add_tickets()
    {
        $concert = factory(Concert::class)->create();


        $concert->addTickets(50);


        $this->assertEquals(50, $concert->ticketsRemaining());

    }

    /** @test */
    function tickets_remaining_does_not_include_tickets_associated_with_an_order()
    {

        $concert = factory(Concert::class)->create();


        $concert->tickets()->saveMany(factory(Ticket::class, 3)->create(['order_id' => 1]));
        $concert->tickets()->saveMany(factory(Ticket::class, 2)->create(['order_id' => null]));


        $this->assertEquals(2, $concert->ticketsRemaining());

    }

    /** @test */
    function tickets_sold_only_include_tickets_associated_with_an_order()
    {
        $concert = factory(Concert::class)->create();


        $concert->tickets()->saveMany(factory(Ticket::class, 3)->create(['order_id' => 1]));
        $concert->tickets()->saveMany(factory(Ticket::class, 2)->create(['order_id' => null]));


        $this->assertEquals(3, $concert->ticketsSold());
    }

    /** @test */
    function total_tickets_include_all_tickets()
    {
        $concert = factory(Concert::class)->create();


        $concert->tickets()->saveMany(factory(Ticket::class, 3)->create(['order_id' => 1]));
        $concert->tickets()->saveMany(factory(Ticket::class, 2)->create(['order_id' => null]));


        $this->assertEquals(5, $concert->totalTickets());
    }

    /** @test */
    function calculating_the_percentage_of_tickets_sold()
    {
        $concert = factory(Concert::class)->create();


        $concert->tickets()->saveMany(factory(Ticket::class, 2)->create(['order_id' => 1]));
        $concert->tickets()->saveMany(factory(Ticket::class, 5)->create(['order_id' => null]));


        $this->assertEquals(28.5714286, $concert->percentSoldOut(), '', 0.01);
    }

    /** @test */
    function calculating_the_revenue_in_dollars()
    {
        $concert = factory(Concert::class)->create();

        $orderA = factory(Order::class)->create(['amount' => 3850]);
        $orderB = factory(Order::class)->create(['amount' => 9625]);


        $concert->tickets()->saveMany(factory(Ticket::class, 2)->create(['order_id' => $orderA->id]));
        $concert->tickets()->saveMany(factory(Ticket::class, 5)->create(['order_id' => $orderB->id]));


        $this->assertEquals(134.75, $concert->revenueInDollars());
    }

    /** @test */
    function trying_to_reserve_more_tickets_than_remain_throws_an_exception()
    {
        $concert = factory(Concert::class)->create()->addTickets(10);

        try {
            $reservation = $concert->reserveTickets(11, 'john@example.com');
        } catch (NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('jane@example.com'));


            $this->assertEquals(10, $concert->ticketsRemaining());
            return;

        }
        $this->fail('order succeeded even with not enough tickets');
    }


    /** @test */
    function can_reserve_available_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);

        $this->assertEquals(3, $concert->ticketsRemaining());


        $reservation = $concert->reserveTickets(2, 'john@example.com');


        $this->assertCount(2, $reservation->tickets());

        $this->assertEquals('john@example.com', $reservation->email());


        $this->assertEquals(1, $concert->ticketsRemaining());
    }

    /** @test */
    function cannot_reserve_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);

        $order = factory(Order::class)->create();

        $order->tickets()->saveMany($concert->tickets->take(2));


//        $concert->orderTickets('jane@example.com', 2);


        try {
            $concert->reserveTickets(2, 'john@example.com');
        } catch (NotEnoughTicketsException $e) {
            $this->assertEquals(1, $concert->ticketsRemaining());
            return;

        }


        $this->fail("reserving tickets succeeded even though the tickets have already been purchased");

    }


    /** @test */
    function cannot_reserve_tickets_that_have_already_been_reserved()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);


        $concert->reserveTickets(2, 'john@example.com');


        try {
            $concert->reserveTickets(2, 'jane@example.com');
        } catch (NotEnoughTicketsException $e) {
            $this->assertEquals(1, $concert->ticketsRemaining());
            return;

        }


        $this->fail("reserving tickets succeeded even though the tickets have already been reserved");

    }
}
