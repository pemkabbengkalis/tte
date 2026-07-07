<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Base class untuk Feature Tests yang merender halaman Blade.
 * Menonaktifkan Vite agar test tidak memerlukan asset yang sudah di-build.
 */
abstract class FeatureTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
