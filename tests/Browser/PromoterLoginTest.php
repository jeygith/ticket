<?php

namespace Tests\Browser;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PromoterLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    function logging_in_successfully()
    {

        $user = factory(User::class)->create([

            'email' => 'jane@example.com',
            'password' => bcrypt('super-secret-password')
        ]);


        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'jane@example.com')
                ->type('password', 'super-secret-password')
                ->press('Log in')
                ->assertPathIs('/backstage/concerts');
        });
    }


    /** @test */
    function logging_in_with_invalid_credentials()
    {
        $user = factory(User::class)->create([

            'email' => 'jane@example.com',
            'password' => bcrypt('super-secret-password')
        ]);


        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'jane@example.com')
                ->type('password', 'wrong-password')
                ->press('Log in')
                ->assertPathIs('/login')
                ->assertSee('credentials do not match');
        });
    }

}
