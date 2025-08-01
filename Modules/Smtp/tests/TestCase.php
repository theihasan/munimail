<?php

namespace Modules\SMTP\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Log;
use React\EventLoop\Loop;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable logging during tests unless explicitly needed
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
        Log::shouldReceive('warning')->andReturn(true);
        
        // Reset ReactPHP loop for each test
        if (Loop::get()) {
            Loop::get()->stop();
        }
    }

    protected function tearDown(): void
    {
        // Clean up ReactPHP loop
        if (Loop::get()) {
            Loop::get()->stop();
        }
        
        parent::tearDown();
    }

    /**
     * Create a temporary email file for testing
     */
    protected function createTempEmailFile(string $content = null): string
    {
        $content = $content ?? $this->getTestEmailContent();
        $tempFile = tempnam(sys_get_temp_dir(), 'smtp_test_');
        file_put_contents($tempFile, $content);
        
        return $tempFile;
    }

    /**
     * Get test email content
     */
    protected function getTestEmailContent(): string
    {
        return "From: test@example.com\r\n" .
               "To: recipient@example.com\r\n" .
               "Subject: Test Email\r\n" .
               "Date: " . now()->toRfc2822String() . "\r\n" .
               "Message-ID: <test-" . uniqid() . "@example.com>\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n" .
               "Content-Transfer-Encoding: 7bit\r\n" .
               "\r\n" .
               "This is a test email content.\r\n";
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
} 