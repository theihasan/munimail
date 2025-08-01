<?php

namespace Modules\SMTP\Tests\Unit\Services;

use Modules\SMTP\Services\DnsResolutionService;
use Modules\SMTP\Services\EmailDeliveryService;
use Modules\SMTP\Services\SmtpClientService;
use Modules\SMTP\Tests\TestCase;
use Mockery;

class EmailDeliveryServiceTest extends TestCase
{
    private EmailDeliveryService $deliveryService;
    private $mockDnsService;
    private $mockSmtpClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockDnsService = Mockery::mock(DnsResolutionService::class);
        $this->mockSmtpClient = Mockery::mock(SmtpClientService::class);
        
        $this->deliveryService = new EmailDeliveryService(
            $this->mockDnsService,
            $this->mockSmtpClient
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_check_if_delivery_is_enabled()
    {
        config(['smtp.delivery.enabled' => true]);
        
        $this->assertTrue($this->deliveryService->isDeliveryEnabled());
        
        config(['smtp.delivery.enabled' => false]);
        
        $this->assertFalse($this->deliveryService->isDeliveryEnabled());
    }

    /** @test */
    public function it_can_extract_domain_from_email()
    {
        $reflection = new \ReflectionClass($this->deliveryService);
        $method = $reflection->getMethod('extractDomain');
        $method->setAccessible(true);
        
        $this->assertEquals('example.com', $method->invoke($this->deliveryService, 'test@example.com'));
        $this->assertEquals('localhost', $method->invoke($this->deliveryService, 'test@localhost'));
        $this->assertEquals('localhost', $method->invoke($this->deliveryService, 'invalid-email'));
    }

    /** @test */
    public function it_can_filter_external_recipients()
    {
        config(['smtp.delivery.internal_domains' => ['localhost', '127.0.0.1']]);
        
        $recipients = [
            'test@example.com',
            'user@localhost',
            'admin@127.0.0.1',
            'external@gmail.com'
        ];
        
        $reflection = new \ReflectionClass($this->deliveryService);
        $method = $reflection->getMethod('filterExternalRecipients');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->deliveryService, $recipients);
        
        $this->assertCount(2, $result);
        $this->assertContains('test@example.com', $result);
        $this->assertContains('external@gmail.com', $result);
        $this->assertNotContains('user@localhost', $result);
        $this->assertNotContains('admin@127.0.0.1', $result);
    }

    /** @test */
    public function it_returns_true_when_no_external_recipients()
    {
        config(['smtp.delivery.enabled' => true]);
        
        $tempFile = $this->createTempEmailFile();
        
        $recipients = ['test@localhost', 'user@127.0.0.1'];
        
        $result = $this->deliveryService->sendEmail($tempFile, 'sender@example.com', $recipients);
        
        $this->assertTrue($result);
        $this->cleanupTempFile($tempFile);
    }



    /** @test */
    public function it_has_proper_constructor()
    {
        $dnsService = new DnsResolutionService();
        $smtpClient = new SmtpClientService();
        
        $deliveryService = new EmailDeliveryService($dnsService, $smtpClient);
        
        $this->assertInstanceOf(EmailDeliveryService::class, $deliveryService);
    }

    /** @test */
    public function it_can_handle_internal_domains_configuration()
    {
        config(['smtp.delivery.internal_domains' => ['test.com', 'internal.org']]);
        
        $reflection = new \ReflectionClass($this->deliveryService);
        $method = $reflection->getMethod('getInternalDomains');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->deliveryService);
        
        $this->assertEquals(['test.com', 'internal.org'], $result);
    }


} 