<?php

namespace Modules\SMTP\Services;

use Illuminate\Support\Facades\Log;

class EmailDeliveryService
{
    public function __construct(
        private readonly DnsResolutionService $dnsService,
        private readonly SmtpClientService $smtpClient
    ) {
    }

    /**
     * Check if email delivery is enabled
     */
    public function isDeliveryEnabled(): bool
    {
        return env('SMTP_ENABLE_DELIVERY', false);
    }

    /**
     * Get internal domains from environment
     */
    private function getInternalDomains(): array
    {
        return array_map('trim', explode(',', env('SMTP_INTERNAL_DOMAINS', 'localhost,127.0.0.1')));
    }

    /**
     * Send email to external recipients
     */
    public function sendEmail(string $emailFilePath, ?string $sender, array $recipients): bool
    {
        try {
            if (!file_exists($emailFilePath)) {
                throw new \Exception("Email file not found: {$emailFilePath}");
            }

            $emailContent = file_get_contents($emailFilePath);
            
            // Filter out internal recipients (keep only external)
            $externalRecipients = $this->filterExternalRecipients($recipients);
            
            if (empty($externalRecipients)) {
                Log::info("No external recipients found for email from {$sender}");
                return true; 
            }

            // Send to each external recipient
            foreach ($externalRecipients as $recipient) {
                $this->sendDirectToMx($emailContent, $sender, $recipient);
            }

            Log::info("Email from {$sender} delivered to " . implode(', ', $externalRecipients));
            return true;

        } catch (\Exception $e) {
            Log::error("Email delivery failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email directly to MX server
     */
    private function sendDirectToMx(string $emailContent, ?string $sender, string $recipient): void
    {
        try {
            $domain = $this->extractDomain($recipient);
            
            // Resolve MX records
            $mxHost = $this->dnsService->getBestMxServer($domain);
            $mxIp = $this->dnsService->getMxServerIp($mxHost);
            
            Log::info("Resolved MX server for {$domain}: {$mxHost} ({$mxIp})");
            
            // Send email directly to MX server
            $success = $this->smtpClient->sendToMxServer(
                $mxHost,
                $mxIp,
                $sender ?? config('mail.from.address'),
                $recipient,
                $emailContent
            );
            
            if (!$success) {
                throw new \Exception("Failed to deliver email to {$mxHost}");
            }
            
            Log::info("Email delivered directly to MX server {$mxHost}");
            
        } catch (\Exception $e) {
            Log::error("Direct MX delivery failed for {$recipient}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Filter out internal recipients (keep only external)
     */
    private function filterExternalRecipients(array $recipients): array
    {
        $externalRecipients = [];

        $externalRecipients = collect($recipients)
            ->reject(function ($recipient) {
                $domain = $this->extractDomain($recipient);
                return in_array($domain, $this->getInternalDomains());
            })
            ->values()
            ->all();

        return $externalRecipients;
    }

    /**
     * Extract domain from email address
     */
    private function extractDomain(string $email): string
    {
        $parts = explode('@', $email);
        return count($parts) > 1 ? $parts[1] : 'localhost';
    }
} 