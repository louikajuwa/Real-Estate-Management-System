<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Layouts use vite; the compiled manifest does not exist in the test environment.
        $this->withoutVite();
    }
}
