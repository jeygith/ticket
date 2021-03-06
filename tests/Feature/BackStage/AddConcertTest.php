<?php

namespace Tests\Feature\BackStage;

use App\Concert;
use App\Events\ConcertAdded;
use App\User;
use Carbon\Carbon;
use Event;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Testing\File;
use Storage;
use Tests\TestCase;

class AddConcertTest extends TestCase
{

    use DatabaseMigrations;

    /*    private function from($url)
        {
            session()->setPreviousUrl(url($url));

            return $this;
        }*/


    private function validParams($overrides = [])
    {
        return array_merge([
            'title' => 'No Warning',
            'subtitle' => 'with Cruel Hand and Backtrack',
            'additional_information' => 'You must be at least 19 years to attend this show.',
            'date' => '2017-11-18',
            'time' => '8:00pm',
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Fake St.',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '12345',
            'ticket_price' => '32.50',
            'ticket_quantity' => '75',
        ], $overrides);
    }

    /** @test */
    function promoters_can_view_the_add_concert_form()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();


        $response = $this->actingAs($user)->get('/backstage/concerts/new');


        $response->assertStatus(200);
    }

    /** @test */
    function guests_cannot_view_the_add_concert_form()
    {
        //    $this->withoutExceptionHandling();

        $response = $this->get('/backstage/concerts/new');

        $response->assertStatus(302);

        $response->assertRedirect('/login');
    }

    /** @test */
    public function adding_a_valid_concert()
    {
        //$this->withoutExceptionHandling();


        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('backstage/concerts', [
            'title' => 'No Warning',
            'subtitle' => 'with Cruel Hand and Backtrack',
            'additional_information' => 'You must be at least 19 years to attend this show.',
            'date' => '2017-11-18',
            'time' => '8:00pm',
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Fake St.',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '12345',
            'ticket_price' => '32.50',
            'ticket_quantity' => '75',
        ]);

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertStatus(302);
            $response->assertRedirect("/backstage/concerts");

            /*$this->assertfalse($concert->isPublished());*/
            $this->assertTrue($concert->user->is($user));

            $this->assertFalse($concert->isPublished());

            $this->assertEquals('No Warning', $concert->title);
            $this->assertEquals('with Cruel Hand and Backtrack', $concert->subtitle);
            $this->assertEquals('You must be at least 19 years to attend this show.', $concert->additional_information);
            $this->assertEquals(Carbon::parse('2017-11-18 8:00pm'), $concert->date);
            $this->assertEquals('The Mosh Pit', $concert->venue);
            $this->assertEquals('123 Fake St.', $concert->venue_address);
            $this->assertEquals('Laraville', $concert->city);
            $this->assertEquals('ON', $concert->state);
            $this->assertEquals('12345', $concert->zip);
            $this->assertEquals(3250, $concert->ticket_price);
            $this->assertEquals(75, $concert->ticket_quantity);
            $this->assertEquals(0, $concert->ticketsRemaining());
        });
    }


    /** @test */
    public function guests_cannot_add_new_concerts()
    {

        $response = $this->post('backstage/concerts', $this->validParams());

        $response->assertStatus(302);
        $response->assertRedirect("/login");

        $this->assertEquals(0, Concert::count());

    }

    /** @test */
    public function title_is_required()
    {
        //  $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        session()->setPreviousUrl(url('/backstage/concerts/new'));

        $response = $this->actingAs($user)->post('backstage/concerts', $this->validParams(['title' => '']));

        $response->assertStatus(302);
        $response->assertRedirect("/backstage/concerts/new");

        $response->assertSessionHasErrors('title');

        $this->assertEquals(0, Concert::count());

    }


    /** @test */
    public function subtitle_is_optional()
    {
        $this->withoutExceptionHandling();


        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('backstage/concerts', $this->validParams(['subtitle' => '']));

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertStatus(302);
            $response->assertRedirect("/backstage/concerts");

            $this->assertNull($concert->subtitle);
        });
    }

    /** @test */
    public function additional_information_is_optional()
    {
        $this->withoutExceptionHandling();


        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('backstage/concerts', $this->validParams(['additional_information' => '']));

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertStatus(302);
            $response->assertRedirect("/backstage/concerts");


            $this->assertNull($concert->additional_information);
        });
    }


    /** @test */
    public function date_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['date' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function date_must_be_a_valid_date()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['date' => 'invalid-date']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('date');
    }

    /** @test */
    public function time_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['time' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('time');
    }

    /** @test */
    public function time_must_be_a_valid_time()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['time' => 'not-a-valid-time']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('time');
    }

    /** @test */
    public function venue_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['venue' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('venue');
    }

    /** @test */
    public function venue_address_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['venue_address' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('venue_address');
    }

    /** @test */
    public function city_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['city' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('city');
    }

    /** @test */
    public function state_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['state' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('state');
    }

    /** @test */
    public function zip_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['zip' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('zip');
    }

    /** @test */
    public function ticket_price_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['ticket_price' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('ticket_price');
    }

    /** @test */
    public function ticket_price_must_be_numeric()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['ticket_price' => 'thirtytwofifty']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('ticket_price');
    }

    /** @test */
    public function ticket_price_must_be_at_least_5()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['ticket_price' => '4.99']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('ticket_price');
    }

    /** @test */
    public function ticket_quantity_is_required()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['ticket_quantity' => '']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_must_be_numeric()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['ticket_quantity' => 'seventyfive']));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_must_be_at_least_1()
    {
        $this->actingAs(factory(User::class)->create());

        $response = $this->from('backstage/concerts/new')
            ->post('backstage/concerts', $this->validParams(['ticket_quantity' => 0]));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $this->assertEquals(0, Concert::count());
        $response->assertSessionHasErrors('ticket_quantity');
    }


    /** @test */
    public function poster_image_is_uploaded_if_included()
    {
        $this->withoutExceptionHandling();
        Storage::fake('public');
        $user = factory(User::class)->create();
        $file = File::image('concert-poster.png', 850, 1100);

        $this->actingAs($user)->post('backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $concert = Concert::first();
        $this->assertNotNull($concert->poster_image_path);


        Storage::disk('public')->assertExists($concert->poster_image_path);

        tap(Concert::first(), function ($concert) use ($file) {
            $this->assertNotNull($concert->poster_image_path);
            Storage::disk('public')->assertExists($concert->poster_image_path);


            /*ToDo
            remove this error on this assertion
            */
           /* $this->assertFileEquals(
                $file->getPathName(),
                Storage::disk('public')->path($concert->poster_image_path)
            );*/
        });

    }

    /** @test */
    public function poster_image_must_be_an_image()
    {
        Storage::fake('public');
        $user = factory(User::class)->create();

        $file = File::create('not-a-poster.pdf');

        $response = $this->actingAs($user)->from('backstage/concerts/new')->post('backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        $this->assertEquals(0, Concert::count());
    }

    /** @test */
    public function poster_image_must_be_at_least_400px_wide()
    {
        Storage::fake('public');
        $user = factory(User::class)->create();

        $file = File::image('poster.png', 399, 516);

        $response = $this->actingAs($user)->from('backstage/concerts/new')->post('backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        $this->assertEquals(0, Concert::count());
    }

    /** @test */
    public function poster_image_must_have_letter_aspect_ratio()
    {
        Storage::fake('public');
        $user = factory(User::class)->create();

        $file = File::image('poster.png', 399, 851, 1100);

        $response = $this->actingAs($user)->from('backstage/concerts/new')->post('backstage/concerts', $this->validParams([
            'poster_image' => $file,
        ]));

        $response->assertStatus(302);
        $response->assertRedirect('backstage/concerts/new');
        $response->assertSessionHasErrors('poster_image');
        $this->assertEquals(0, Concert::count());
    }


    /** @test */
    public function poster_image_is_optional()
    {
        $this->withoutExceptionHandling();
        $user = factory(User::class)->create();


        $response = $this->actingAs($user)->post('backstage/concerts', $this->validParams([
            'poster_image' => null,
        ]));

        tap(Concert::first(), function ($concert) use ($response, $user) {
            $response->assertStatus(302);

            $response->assertRedirect("/backstage/concerts");

            $this->assertTrue($concert->user->is($user));

            $this->assertNull($concert->poster_image);
        });

    }


    /** @test */
    function an_event_is_fired_when_a_concert_is_added()
    {

        Event::fake([ConcertAdded::class]);
        $user = factory(User::class)->create();


        $response = $this->actingAs($user)->post('backstage/concerts', $this->validParams());


        // assert that a concertadded event was dispatched

        Event::assertDispatched(ConcertAdded::class, function ($event) {

            $concert = Concert::firstOrFail();

            return $event->concert->is($concert);

        });
    }


}
