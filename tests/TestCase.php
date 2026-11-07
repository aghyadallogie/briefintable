<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: .env points at a hosted Postgres, and RefreshDatabase wipes whatever it connects to.
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('Refusing to run tests against the "'.config('database.default').'" connection. Check phpunit.xml.');
        }

        // Same idea for the cache: tests call Cache::flush(), which must never hit a shared store.
        if (config('cache.default') !== 'array') {
            throw new RuntimeException('Refusing to run tests against the "'.config('cache.default').'" cache store. Check phpunit.xml.');
        }
    }
}
