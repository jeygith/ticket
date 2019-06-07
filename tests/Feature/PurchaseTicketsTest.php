<?php

namespace Tests\Feature;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Facades\OrderConfirmationNumber;
use App\Facades\TicketCode;
use App\Mail\OrderConfirmationEmail;
use App\User;
use ConcertFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mail;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;
    protected $paymentGateway;


    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
        Mail::fake();

    }

    private function orderTickets($concert, $params)
    {
        $savedRequest = $this->app['request'];

        $this->response = $this->json('POST', "/concerts/{$concert->id}/orders", $params);
        $this->app['request'] = $savedRequest;


    }

    private function assertResponseStatus($status)
    {

        $this->response->assertStatus($status);
    }


    private function seeJson($data)
    {
        $this->response->assertJson($data);
    }

    private function decodeResponseJson()
    {
        return $this->response->decodeResponseJson();
    }

    private function assertValidationError($field)
    {
        $this->assertResponseStatus(422);
        $this->assertArrayHasKey($field, $this->decodeResponseJson()["errors"]);
    }

    /** @test */
    function customer_can_purchase_tickets_to_a_published_concert()
    {

        $this->withoutExceptionHandling();

        Mail::fake();

        // arrange
        // create a concert


        /*  $orderConfirmationNumberGenerator = Mockery::mock(OrderConfirmationNumberGenerator::class, [
              'generate' => 'ORDERCONFIRMATION1234'
          ]);*/
        /*        $this->app->instance(OrderConfirmationNumberGenerator::class, $orderConfirmationNumberGenerator);*/


        OrderConfirmationNumber::shouldReceive('generate')->andReturn('ORDERCONFIRMATION1234');
        TicketCode::shouldReceive('generateFor')->andReturn('TICKETCODE1', 'TICKETCODE2', 'TICKETCODE3');


        /*      $concert = factory(Concert::class)->states('published')
                  ->create(['ticket_price' => 3250]);


              $concert->publish();*/
        $user = factory(User::class)->create(['stripe_account_id' => 'test_acc_1234']);


        $concert = ConcertFactory::createPublished([
            'ticket_price' => 3250,
            'ticket_quantity' => 3,
            'user_id' => $user->id,
        ]);
        //act
        //purchase tickets


        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        //assert

        $this->assertResponseStatus(201);
        // make sure customer was charged correct amount


        $this->seeJson([
            'confirmation_number' => 'ORDERCONFIRMATION1234',
            'email' => 'john@example.com',
            'amount' => 9750,
            'tickets' => [
                ['code' => 'TICKETCODE1'],
                ['code' => 'TICKETCODE2'],
                ['code' => 'TICKETCODE3']
            ]
        ]);

        $this->assertEquals(9750, $this->paymentGateway->totalChargesFor('test_acc_1234'));
        // make sure order exists

        $this->assertTrue($concert->hasOrderFor('john@example.com'));

        $order = $concert->ordersFor('john@example.com')->first();

        $this->assertEquals(3, $order->ticketQuantity());

        Mail::assertSent(OrderConfirmationEmail::class, function ($mail) use ($order) {
            return $mail->hasTo('john@example.com')
                && $mail->order->id == $order->id;
        });

    }


    /** @test */
    function cannot_purchase_tickets_to_an_unpublished_concert()
    {

        $concert = factory(Concert::class)->states('unpublished')->create()->addTickets(3);


        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);


        $this->assertResponseStatus(404);

        $this->assertFalse($concert->hasOrderFor('john@example.com'));


        $this->assertEquals(0, $this->paymentGateway->totalCharges());


    }

    /** @test */
    function an_order_is_not_created_if_payment_fails()
    {
        $this->withoutExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250])->addTickets(3);


        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token'
        ]);


        $this->assertResponseStatus(422);

        $this->assertFalse($concert->hasOrderFor('john@example.com'));

        $this->assertEquals(3, $concert->ticketsRemaining());

    }

    /** @test */
    function cannot_purchase_more_tickets_than_remain()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(50);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(422);


        $this->assertFalse($concert->hasOrderFor('john@example.com'));


        $this->assertEquals(0, $this->paymentGateway->totalCharges());

        $this->assertEquals(50, $concert->ticketsRemaining());
    }


    /** @test */
    function cannot_purchase_tickets_another_customer_is_already_trying_to_purchase()
    {

        $this->withoutExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create(["ticket_price" => 1200])->addTickets(3);


        $this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert) {


            $this->orderTickets($concert, [
                'email' => 'personB@example.com',
                'ticket_quantity' => 3,
                'payment_token' => $this->paymentGateway->getValidTestToken()
            ]);


            $this->assertResponseStatus(422);


            $this->assertFalse($concert->hasOrderFor('personB@example.com'));


            $this->assertEquals(0, $this->paymentGateway->totalCharges());


        });


        $this->orderTickets($concert, [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // dd($concert->orders()->first()->toArray());


        $this->assertEquals(3600, $this->paymentGateway->totalCharges());

        $this->assertTrue($concert->hasOrderFor('personA@example.com'));

        $this->assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());


        // find tickets for person A
        // find tickets for person B

        // Attempt to charge person A
        // Attempt to charge person B

        // Create an order for person A
        // Create an order for person B
    }


    /** @test */
    function email_is_required_to_purchase_tickets()
    {

        // $this->withoutExceptionHandling();


        $concert = factory(Concert::class)->states('published')->create();

        //act
        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);


        $this->assertValidationError('email');
    }


    /** @test */
    public function email_must_be_valid_to_purchase_tickets()
    {

        // $this->withoutExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create();
        $this->orderTickets($concert, [
            'email' => 'not-an-email-address',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);
        $this->assertValidationError("email");
    }

    /** @test */
    public function ticket_quantity_is_required_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create();
        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);
        $this->assertValidationError("ticket_quantity");

    }

    /** @test */
    public function ticket_quantity_must_be_greater_than_zero()
    {

        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);
        $this->assertValidationError("ticket_quantity");

    }

    /** @test */
    public function ticket_quantity_must_be_an_integer()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 's',
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);
        $this->assertValidationError("ticket_quantity");
    }

    /** @test */
    public function a_payment_token_is_required()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 0,
        ]);
        $this->assertValidationError("payment_token");


    }


}
