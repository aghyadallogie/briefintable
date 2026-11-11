<?php

namespace Tests\Unit;

use App\Support\BriefingsCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BriefingsCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_keys_are_stable_until_bumped(): void
    {
        $this->assertSame(BriefingsCache::listKey(1, 10), BriefingsCache::listKey(1, 10));
    }

    public function test_each_page_has_its_own_key(): void
    {
        $this->assertNotSame(BriefingsCache::listKey(1, 10), BriefingsCache::listKey(2, 10));
    }

    public function test_changing_the_page_size_changes_the_key(): void
    {
        $this->assertNotSame(BriefingsCache::listKey(1, 10), BriefingsCache::listKey(1, 3));
    }

    public function test_the_published_count_is_resolved_once_per_version(): void
    {
        $calls = 0;
        $resolve = function () use (&$calls) {
            $calls++;

            return 7;
        };

        $this->assertSame(7, BriefingsCache::publishedCount($resolve));
        $this->assertSame(7, BriefingsCache::publishedCount($resolve));
        $this->assertSame(1, $calls);

        BriefingsCache::bump();

        $this->assertSame(7, BriefingsCache::publishedCount($resolve));
        $this->assertSame(2, $calls);
    }

    public function test_remember_runs_the_callback_once_per_key_and_forgets_it_after_a_bump(): void
    {
        $calls = 0;
        $resolve = function () use (&$calls) {
            return 'value-'.++$calls;
        };

        $this->assertSame('value-1', BriefingsCache::remember(BriefingsCache::homeKey(), $resolve));
        $this->assertSame('value-1', BriefingsCache::remember(BriefingsCache::homeKey(), $resolve));

        BriefingsCache::bump();

        $this->assertSame('value-2', BriefingsCache::remember(BriefingsCache::homeKey(), $resolve));
    }

    public function test_a_published_count_of_zero_is_cached_too(): void
    {
        $calls = 0;
        $resolve = function () use (&$calls) {
            $calls++;

            return 0;
        };

        BriefingsCache::publishedCount($resolve);
        BriefingsCache::publishedCount($resolve);

        $this->assertSame(1, $calls);
    }

    public function test_every_bump_makes_a_version_never_used_before(): void
    {
        $keys = [BriefingsCache::listKey(1, 10)];

        for ($i = 0; $i < 20; $i++) {
            BriefingsCache::bump();
            $keys[] = BriefingsCache::listKey(1, 10);
        }

        // A counter that two bumps could write the same value to would repeat a key here.
        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    public function test_bump_changes_every_key(): void
    {
        $before = [BriefingsCache::listKey(1, 10), BriefingsCache::listKey(2, 10)];

        BriefingsCache::bump();

        $this->assertNotContains(BriefingsCache::listKey(1, 10), $before);
        $this->assertNotContains(BriefingsCache::listKey(2, 10), $before);
    }
}
