<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the JWT guard cached user between test methods.
        // auth("api")->login() sets $this->user on the guard instance, which
        // persists across tests because AuthManager caches guard instances.
        // Without this, unauthenticated tests (no token) would still see the
        // cached user from a prior test and pass auth:api middleware.
        auth("api")->forgetUser();
    }
}
