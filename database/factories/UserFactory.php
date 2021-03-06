<?php

use App\Concert;
use App\Invitation;
use App\Order;
use App\Ticket;
use App\User;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'remember_token' => Str::random(10),
        'stripe_account_id' => 'test_acct_1234',
        'stripe_access_token' => 'test_token',
    ];
});


$factory->define(Concert::class, function (Faker $faker) {

    return [
        'user_id' => function () {

            return factory(User::class)->create()->id;
        },
        'title' => 'Example Band',
        'subtitle' => 'with FAke openers',
        'date' => Carbon::parse('+2 weeks'),
        'venue' => 'The Example Theatre',
        'venue_address' => '123 Example Lane',
        'city' => 'Fakeville',
        'state' => 'ON',
        'zip' => '90210',
        'additional_information' => 'Some Sample Additional information that is just a bunch of gibberish.',
        'ticket_price' => 2000,
        'ticket_quantity' => 5,

    ];
});

$factory->state(Concert::class, 'published', function ($faker) {
    return [
        'published_at' => Carbon::parse('-1 week')
    ];
});


$factory->state(Concert::class, 'unpublished', function ($faker) {
    return [
        'published_at' => null
    ];
});


$factory->define(Ticket::class, function (Faker $faker) {

    return [
        'concert_id' => function () {
            return factory(Concert::class)->create()->id;
        }
    ];
});

$factory->state(Ticket::class, 'reserved', function ($faker) {
    return [
        'reserved_at' => Carbon::now()
    ];
});

$factory->define(Order::class, function (Faker $faker) {

    return [
        'amount' => 5250,
        'email' => 'somebody@email.com',
        'confirmation_number' => 'ORDERCONFIRMATION1234',
        'card_last_four' => '1234'

    ];
});


$factory->define(Invitation::class, function (Faker $faker) {

    return [
        'code' => 'TESTCODE1234',
        'email' => 'somebody@email.com'
    ];
});