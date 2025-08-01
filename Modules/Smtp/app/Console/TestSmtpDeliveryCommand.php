<?php

namespace Modules\SMTP\Console;

use Illuminate\Console\Command;
use Modules\SMTP\Services\DnsResolutionService;
use Modules\SMTP\Services\SmtpClientService;
use Modules\SMTP\Services\EmailDeliveryService;

class TestSmtpDeliveryCommand extends Command
{
    protected $signature = 'smtp:test-delivery {domain : Domain to test} {--sender= : Test sender email} {--recipient= : Test recipient email}';
    protected $description = 'Test SMTP delivery system with DNS resolution and direct MX delivery';

    public function handle(): int
    {
        $domain = $this->argument('domain');
        $sender = $this->option('sender') ?? 'test@localhost';
        $recipient = $this->option('recipient') ?? "test@{$domain}";
        
        $this->info('🔍 Testing SMTP Delivery System');
        $this->line('');
        
        try {
            // Step 1: Test DNS resolution
            $this->info('1. Testing DNS resolution...');
            $dnsService = new DnsResolutionService();
            
            $mxRecords = $dnsService->resolveMxRecords($domain);
            $this->info("✅ Found " . count($mxRecords) . " MX records for {$domain}");
            
            foreach ($mxRecords as $i => $record) {
                $this->line("   " . ($i + 1) . ". {$record['host']} (priority: {$record['priority']})");
            }
            
            // Step 2: Get best MX server
            $this->info('2. Getting best MX server...');
            $bestMx = $dnsService->getBestMxServer($domain);
            $this->info("✅ Best MX server: {$bestMx}");
            
            // Step 3: Resolve IP address
            $this->info('3. Resolving IP address...');
            $mxIp = $dnsService->getMxServerIp($bestMx);
            $this->info("✅ MX server IP: {$mxIp}");
            
            // Step 4: Test SMTP connection
            $this->info('4. Testing SMTP connection...');
            $smtpClient = new SmtpClientService();
            $connectionTest = $smtpClient->sendToMxServer(
                $bestMx,
                $mxIp,
                $sender,
                $recipient,
                $this->createTestEmail($sender, $recipient)
            );
            
            if ($connectionTest) {
                $this->info("✅ SMTP connection and delivery test successful");
            } else {
                $this->error("❌ SMTP connection test failed");
                return Command::FAILURE;
            }
            
            // Step 5: Test email delivery service
            $this->info('5. Testing email delivery service...');
            $deliveryService = new EmailDeliveryService($dnsService, $smtpClient);
            
            $this->info("✅ Delivery enabled: " . ($deliveryService->isDeliveryEnabled() ? 'Yes' : 'No'));
            $this->info("✅ Internal domains: " . json_encode(config('smtp.delivery.internal_domains')));
            
            $this->line('');
            $this->info('🎉 All tests passed! SMTP delivery system is working correctly.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function createTestEmail(string $sender, string $recipient): string
    {
        $subject = 'Test Email from Munimail SMTP Server';
        $body = "This is a test email sent from your Munimail SMTP server.\n\n";
        $body .= "Sender: {$sender}\n";
        $body .= "Recipient: {$recipient}\n";
        $body .= "Timestamp: " . now()->toISOString() . "\n\n";
        $body .= "If you received this email, your SMTP server is working correctly!";
        
        $headers = [
            "From: {$sender}",
            "To: {$recipient}",
            "Subject: {$subject}",
            "Date: " . now()->toRfc2822String(),
            "Message-ID: <" . uniqid() . "@munimail.test>",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: 7bit",
        ];
        
        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }
} 