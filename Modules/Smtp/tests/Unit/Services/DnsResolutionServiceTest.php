<?php

namespace Modules\SMTP\Tests\Unit\Services;

use Modules\SMTP\Services\DnsResolutionService;
use Modules\SMTP\Tests\TestCase;

class DnsResolutionServiceTest extends TestCase
{
    private DnsResolutionService $dnsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dnsService = new DnsResolutionService();
    }

    /** @test */
    public function it_can_extract_domain_from_email()
    {
        $reflection = new \ReflectionClass($this->dnsService);
        $method = $reflection->getMethod('extractDomain');
        $method->setAccessible(true);
        
        $this->assertEquals('example.com', $method->invoke($this->dnsService, 'test@example.com'));
        $this->assertEquals('localhost', $method->invoke($this->dnsService, 'test@localhost'));
        $this->assertEquals('localhost', $method->invoke($this->dnsService, 'invalid-email'));
    }

    /** @test */
    public function it_uses_config_for_dns_server()
    {
        config(['smtp.dns.server' => '1.1.1.1']);
        
        $dnsService = new DnsResolutionService();
        
        // Use reflection to check the DNS server
        $reflection = new \ReflectionClass($dnsService);
        $method = $reflection->getMethod('getDnsServer');
        $method->setAccessible(true);
        
        $result = $method->invoke($dnsService);
        
        $this->assertEquals('1.1.1.1', $result);
    }

    /** @test */
    public function it_has_proper_constructor()
    {
        $dnsService = new DnsResolutionService();
        
        $this->assertInstanceOf(DnsResolutionService::class, $dnsService);
    }

    /** @test */
    public function it_can_handle_dns_server_configuration()
    {
        config(['smtp.dns.server' => '8.8.4.4']);
        
        $dnsService = new DnsResolutionService();
        
        $reflection = new \ReflectionClass($dnsService);
        $method = $reflection->getMethod('getDnsServer');
        $method->setAccessible(true);
        
        $result = $method->invoke($dnsService);
        
        $this->assertEquals('8.8.4.4', $result);
    }
} 