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
    }
}
