<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $storagePath = sys_get_temp_dir() . '/salgados-api-testing-storage';

        File::ensureDirectoryExists($storagePath . '/framework/testing/disks');
        File::ensureDirectoryExists($storagePath . '/framework/views');
        File::ensureDirectoryExists($storagePath . '/logs');

        $this->app->useStoragePath($storagePath);

        config([
            'view.compiled' => $storagePath . '/framework/views',
            'filesystems.disks.public.root' => $storagePath . '/app/public',
            'logging.channels.single.path' => $storagePath . '/logs/laravel.log',
            'logging.channels.daily.path' => $storagePath . '/logs/laravel.log',
            'logging.channels.emergency.path' => $storagePath . '/logs/laravel.log',
        ]);
    }
}
