<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Smoke test: the application boots and answers requests.
     *
     * (The stock test requested "/", but the home page is a Statamic entry stored in the
     * database, which does not exist in a fresh test database.)
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/up')->assertOk();
    }
}
