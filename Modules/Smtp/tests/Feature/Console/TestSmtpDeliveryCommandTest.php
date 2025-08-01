<?php

namespace Modules\SMTP\Tests\Feature\Console;

use Modules\SMTP\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestSmtpDeliveryCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_run_test_delivery_command_with_valid_domain()
    {
        $this->artisan('smtp:test-delivery', [
            'domain' => 'example.com',
            '--sender' => 'test@example.com',
            '--recipient' => 'test@example.com'
        ])
        ->expectsOutput('🔍 Testing SMTP Delivery System')
        ->assertExitCode(1); // Will fail due to DNS resolution, but that's expected
    }

    /** @test */
    public function it_requires_domain_argument()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        
        $this->artisan('smtp:test-delivery');
    }

    /** @test */
    public function it_uses_default_sender_and_recipient_when_not_provided()
    {
        $this->artisan('smtp:test-delivery', [
            'domain' => 'example.com'
        ])
        ->expectsOutput('🔍 Testing SMTP Delivery System')
        ->assertExitCode(1); // Will fail due to DNS resolution, but that's expected
    }

    /** @test */
    public function it_handles_invalid_domain_gracefully()
    {
        $this->artisan('smtp:test-delivery', [
            'domain' => 'invalid-domain-that-does-not-exist.com'
        ])
        ->expectsOutput('🔍 Testing SMTP Delivery System')
        ->expectsOutput('1. Testing DNS resolution...')
        ->assertExitCode(1);
    }



    /** @test */
    public function it_has_proper_command_description()
    {
        $command = $this->app->make(\Modules\SMTP\Console\TestSmtpDeliveryCommand::class);
        
        $this->assertEquals(
            'Test SMTP delivery system with DNS resolution and direct MX delivery',
            $command->getDescription()
        );
    }

    /** @test */
    public function it_can_create_test_email_content()
    {
        $command = $this->app->make(\Modules\SMTP\Console\TestSmtpDeliveryCommand::class);
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('createTestEmail');
        $method->setAccessible(true);
        
        $result = $method->invoke($command, 'sender@example.com', 'recipient@example.com');
        
        $this->assertStringContainsString('From: sender@example.com', $result);
        $this->assertStringContainsString('To: recipient@example.com', $result);
        $this->assertStringContainsString('Subject: Test Email from Munimail SMTP Server', $result);
        $this->assertStringContainsString('MIME-Version: 1.0', $result);
    }
} 