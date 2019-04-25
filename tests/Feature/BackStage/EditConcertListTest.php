<?php

namespace Tests\Feature\BackStage;


use App\Concert;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class EditConcertListTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function promoters_can_view_the_edit_form_for_their_own_published_concerts()
    {
        $user = factory(User::class)->create();
        $concert = factory(Concert::class)->states('unpublished')->create(['user_id' => $user->id]);
        $this->assertFalse($concert->isPublished());

        $response = $this->actingAs($user)->get("backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(200);
        $this->assertTrue($response->data('concert')->is($concert));
    }

    /** @test */
    public function promoters_cannot_view_the_edit_form_for_their_own_published_concerts()
    {
        $user = factory(User::class)->create();
        $concert = factory(Concert::class)->states('published')->create(['user_id' => $user->id]);
        $this->assertTrue($concert->isPublished());

        $response = $this->actingAs($user)->get("backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function promoters_cannot_view_the_edit_form_for_other_promoters_concerts()
    {
        $user = factory(User::class)->create();
        $concert = factory(Concert::class)->states('unpublished')->create(['user_id' => $user->id]);
        $otherUser = factory(User::class)->create();

        $response = $this->actingAs($otherUser)->get("backstage/concerts/{$concert->id}/edit");

        $response->assertStatus(404);
    }

    /** @test */
    public function promoters_see_a_404_when_attempting_to_view_the_edit_page_of_a_concert_that_does_not_exist()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get('backstage/concerts/999/edit');

        $response->assertStatus(404);
    }

    /** @test */
    public function guests_are_asked_to_login_when_attempting_to_view_the_edit_form_for_any_concert()
    {
        $user = factory(User::class)->create();
        $usersConcert = factory(Concert::class)->states('unpublished')->create(['user_id' => $user->id]);

        $response = $this->get("backstage/concerts/{$usersConcert->id}/edit");

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }

    /** @test */
    public function guests_are_asked_to_login_when_attempting_to_view_the_edit_form_for_a_concert_that_does_not_exist()
    {
        $user = factory(User::class)->create();

        $response = $this->get("backstage/concerts/999/edit");

        $response->assertStatus(302);
        $response->assertRedirect('login');
    }
}
