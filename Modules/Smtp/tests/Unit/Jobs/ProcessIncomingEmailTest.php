<?php

namespace Modules\SMTP\Tests\Unit\Jobs;

use Modules\SMTP\Jobs\ProcessIncomingEmail;
use Modules\SMTP\Tests\TestCase;
use Modules\SMTP\Exceptions\EmailFileNotFoundException;

class ProcessIncomingEmailTest extends TestCase
{
    /** @test */
    public function it_can_process_incoming_email_job()
    {
        $tempFile = $this->createTempEmailFile();
        
        $job = new ProcessIncomingEmail(
            $tempFile,
            'sender@example.com',
            ['recipient@example.com'],
            1024
        );
        
        $job->handle();
        
        $this->cleanupTempFile($tempFile);
        
        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_email_file()
    {
        $this->expectException(EmailFileNotFoundException::class);
        
        $job = new ProcessIncomingEmail(
            '/nonexistent/file',
            'sender@example.com',
            ['recipient@example.com'],
            1024
        );
        
        $job->handle();
    }

    /** @test */
    public function it_has_proper_constructor()
    {
        $job = new ProcessIncomingEmail(
            '/path/to/file',
            'sender@example.com',
            ['recipient@example.com'],
            1024
        );
        
        $this->assertInstanceOf(ProcessIncomingEmail::class, $job);
        $this->assertEquals('/path/to/file', $job->emailFilePath);
        $this->assertEquals('sender@example.com', $job->sender);
        $this->assertEquals(['recipient@example.com'], $job->recipients);
        $this->assertEquals(1024, $job->emailSize);
    }

    /** @test */
    public function it_can_handle_null_sender()
    {
        $tempFile = $this->createTempEmailFile();
        
        $job = new ProcessIncomingEmail(
            $tempFile,
            null,
            ['recipient@example.com'],
            1024
        );
        
        $job->handle();
        
        $this->cleanupTempFile($tempFile);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_handle_multiple_recipients()
    {
        $tempFile = $this->createTempEmailFile();
        
        $job = new ProcessIncomingEmail(
            $tempFile,
            'sender@example.com',
            ['recipient1@example.com', 'recipient2@example.com'],
            1024
        );
        
        $job->handle();
        
        $this->cleanupTempFile($tempFile);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_handle_empty_recipients()
    {
        $tempFile = $this->createTempEmailFile();
        
        $job = new ProcessIncomingEmail(
            $tempFile,
            'sender@example.com',
            [],
            1024
        );
        
        $job->handle();
        
        $this->cleanupTempFile($tempFile);
        
        $this->assertTrue(true);
    }

    /** @test */
    public function it_has_public_properties()
    {
        $job = new ProcessIncomingEmail(
            '/path/to/file',
            'sender@example.com',
            ['recipient@example.com'],
            1024
        );
        
        $reflection = new \ReflectionClass($job);
        
        $this->assertTrue($reflection->getProperty('emailFilePath')->isPublic());
        $this->assertTrue($reflection->getProperty('sender')->isPublic());
        $this->assertTrue($reflection->getProperty('recipients')->isPublic());
        $this->assertTrue($reflection->getProperty('emailSize')->isPublic());
    }

    /** @test */
    public function it_implements_should_queue_interface()
    {
        $job = new ProcessIncomingEmail(
            '/path/to/file',
            'sender@example.com',
            ['recipient@example.com'],
            1024
        );
        
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }
} 