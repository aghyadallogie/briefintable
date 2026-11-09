<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Tests\TestCase;

/**
 * Every way of ending up on a missing page shows resources/views/errors/404.antlers.html inside the site
 * layout (navbar included), never Laravel's bare "404 | Not Found" screen.
 */
class NotFoundPagesTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = "We couldn't find that page";

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Collection::make('briefings')->routes('/briefings/{slug}')->template('briefings/show')->layout('layout')->save();
        Cache::flush();
    }

    private function contributor(string $id = 'me'): UserContract
    {
        return User::make()->id($id)->email($id.'@example.test')->assignRole('contributor');
    }

    /** @return array{id: string, slug: string} */
    private function briefing(string $author, bool $published): array
    {
        $entry = Entry::make()
            ->collection('briefings')
            ->slug('a-briefing-'.uniqid())
            ->published($published)
            ->data(['title' => 'A briefing', 'content' => 'Body', 'author' => $author]);
        $entry->save();

        return ['id' => $entry->id(), 'slug' => $entry->slug()];
    }

    private function assertStyledNotFound($response): void
    {
        $response->assertNotFound()
            ->assertSee(self::MESSAGE, false)
            ->assertSee('Back to home')
            ->assertSee('Briefintable, home')
            ->assertSee('id="content"', false);
    }

    public function test_an_unknown_url_for_a_guest(): void
    {
        $this->assertStyledNotFound($this->get('/no-such-page'));
    }

    public function test_an_unknown_url_for_a_signed_in_user(): void
    {
        $this->assertStyledNotFound($this->actingAs($this->contributor())->get('/no-such-page'));
    }

    public function test_an_unknown_briefing_slug(): void
    {
        $this->assertStyledNotFound($this->get('/briefings/no-such-briefing'));
    }

    public function test_a_drafts_public_url(): void
    {
        $draft = $this->briefing('me', published: false);

        $this->assertStyledNotFound($this->get('/briefings/'.$draft['slug']));
    }

    public function test_a_list_page_past_the_last_one(): void
    {
        $this->assertStyledNotFound($this->get('/briefings?page=99'));
    }

    public function test_my_drafts_past_the_last_page(): void
    {
        $this->assertStyledNotFound($this->actingAs($this->contributor())->get('/my-drafts?page=99'));
    }

    public function test_an_unknown_draft_details_page(): void
    {
        $this->assertStyledNotFound($this->actingAs($this->contributor())->get(route('drafts.show', 'does-not-exist')));
    }

    public function test_someone_elses_draft_details_page(): void
    {
        $draft = $this->briefing('other', published: false);

        $this->assertStyledNotFound($this->actingAs($this->contributor())->get(route('drafts.show', $draft['id'])));
    }

    public function test_an_unknown_draft_edit_page(): void
    {
        $this->assertStyledNotFound($this->actingAs($this->contributor())->get(route('drafts.edit', 'does-not-exist')));
    }

    public function test_someone_elses_draft_edit_page(): void
    {
        $draft = $this->briefing('other', published: false);

        $this->assertStyledNotFound($this->actingAs($this->contributor())->get(route('drafts.edit', $draft['id'])));
    }

    public function test_json_clients_still_get_json(): void
    {
        $this->actingAs($this->contributor())
            ->getJson(route('drafts.show', 'does-not-exist'))
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_deleting_an_unknown_draft(): void
    {
        $this->assertStyledNotFound($this->actingAs($this->contributor())->delete(route('drafts.destroy', 'does-not-exist')));
    }

    public function test_deleting_someone_elses_draft(): void
    {
        $draft = $this->briefing('other', published: false);

        $this->assertStyledNotFound($this->actingAs($this->contributor())->delete(route('drafts.destroy', $draft['id'])));
    }
}
