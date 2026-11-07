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

    public function test_bump_changes_every_key(): void
    {
        $before = [BriefingsCache::listKey(1, 10), BriefingsCache::listKey(2, 10)];

        BriefingsCache::bump();

        $this->assertNotContains(BriefingsCache::listKey(1, 10), $before);
        $this->assertNotContains(BriefingsCache::listKey(2, 10), $before);
    }
}
