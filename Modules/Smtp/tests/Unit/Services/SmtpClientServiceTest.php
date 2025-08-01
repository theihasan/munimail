<?php

namespace Modules\SMTP\Tests\Unit\Services;

use Modules\SMTP\Services\SmtpClientService;
use Modules\SMTP\Tests\TestCase;

class SmtpClientServiceTest extends TestCase
{
    private SmtpClientService $smtpClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smtpClient = new SmtpClientService();
    }

    /** @test */
    public function it_has_proper_constructor()
    {
        $smtpClient = new SmtpClientService();
        
        $this->assertInstanceOf(SmtpClientService::class, $smtpClient);
    }

    /** @test */
    public function it_can_get_timeout_from_config()
    {
        config(['smtp.client.timeout' => 15]);
        
        $reflection = new \ReflectionClass($this->smtpClient);
        $method = $reflection->getMethod('getTimeout');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->smtpClient);
        
        $this->assertEquals(15, $result);
    }



    /** @test */
    public function it_can_get_tls_options_from_config()
    {
        config(['smtp.client.tls' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ]]);
        
        $reflection = new \ReflectionClass($this->smtpClient);
        $method = $reflection->getMethod('getTlsOptions');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->smtpClient);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['verify_peer']);
        $this->assertTrue($result['verify_peer_name']);
    }



    /** @test */
    public function it_has_readonly_properties()
    {
        $reflection = new \ReflectionClass($this->smtpClient);
        
        $timeoutProperty = $reflection->getProperty('timeout');
        $tlsOptionsProperty = $reflection->getProperty('tlsOptions');
        
        $this->assertTrue($timeoutProperty->isReadOnly());
        $this->assertTrue($tlsOptionsProperty->isReadOnly());
    }

    /** @test */
    public function it_has_private_methods()
    {
        $reflection = new \ReflectionClass($this->smtpClient);
        
        $this->assertTrue($reflection->getMethod('getTimeout')->isPrivate());
        $this->assertTrue($reflection->getMethod('getTlsOptions')->isPrivate());
        $this->assertTrue($reflection->getMethod('connectToServer')->isPrivate());
        $this->assertTrue($reflection->getMethod('performSmtpConversation')->isPrivate());
        $this->assertTrue($reflection->getMethod('sendCommand')->isPrivate());
    }

    /** @test */
    public function it_has_public_send_to_mx_server_method()
    {
        $reflection = new \ReflectionClass($this->smtpClient);
        
        $this->assertTrue($reflection->getMethod('sendToMxServer')->isPublic());
    }
} 