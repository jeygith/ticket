<?php


namespace Tests\Unit\Billing;

use App\Billing\PaymentFailedException;

trait PaymentGatewayContractTests
{
    abstract protected function getPaymentGateway();


    /** @test */
    function charges_with_a_valid_token_are_successful()
    {

        // Create a new gateway
        $paymentGateway = $this->getPaymentGateway();

        // create new charge with a valid stripe token

        $newCharges = $paymentGateway->newChargesDuring(function ($paymentGateway) {
            $paymentGateway->charge(2500, $paymentGateway->getValidTestToken(), env('STRIPE_TEST_PROMOTER_ID'));
        });

        // verify the charge was completed successfully


        $this->assertCount(1, $newCharges);

        $this->assertEquals(2500, $newCharges->map->amount()->sum());

    }

    /** @test */
    function can_get_details_about_a_successful_charge()
    {
        $paymentGateway = $this->getPaymentGateway();

        $charge = $paymentGateway->charge(2500, $paymentGateway->getValidTestToken($paymentGateway::TEST_CARD_NUMBER), env('STRIPE_TEST_PROMOTER_ID'));


        $this->assertEquals(substr(($paymentGateway::TEST_CARD_NUMBER), -4), $charge->cardLastFour());
        $this->assertEquals(2500, $charge->amount());

        $this->assertEquals(env('STRIPE_TEST_PROMOTER_ID'), $charge->destination());


    }


    /** @test */
    function charges_with_an_invalid_payment_token_fail()
    {
        $paymentGateway = $this->getPaymentGateway();
        $newCharges = $paymentGateway->newChargesDuring(function ($paymentGateway) {

            try {

                $paymentGateway->charge(2500, 'invalid-payment-token', env('STRIPE_TEST_PROMOTER_ID'));

            } catch (PaymentFailedException $e) {
                return;
            }

            $this->fail('Charging with an invalid payment token did not throw a PaymentFailedException');


        });

        $this->assertCount(0, $newCharges);


    }

}