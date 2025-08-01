<?php

namespace Modules\SMTP\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\SMTP\Exceptions\EmailFileNotFoundException;

class ProcessIncomingEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $emailFilePath,
        public ?string $sender,
        public array $recipients,
        public int $emailSize
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!file_exists($this->emailFilePath)) {
            Log::error("Email file not found: {$this->emailFilePath}");
            throw new EmailFileNotFoundException("Email file not found: {$this->emailFilePath}");
        }

        $emailContent = file_get_contents($this->emailFilePath);
        
        Log::info("Processing email from {$this->sender} to " . implode(', ', $this->recipients) . " (Size: {$this->emailSize} bytes)");
        
        // Send email to external recipients via direct MX delivery
        $this->deliverEmail();
        
        $this->moveEmailToCur();
    }

    /**
     * Deliver email to external recipients
     */
    private function deliverEmail(): void
    {
        try {
            $deliveryService = new \Modules\SMTP\Services\EmailDeliveryService(
                new \Modules\SMTP\Services\DnsResolutionService(),
                new \Modules\SMTP\Services\SmtpClientService()
            );
            
            if (!$deliveryService->isDeliveryEnabled()) {
                Log::info("Email delivery is disabled - skipping external delivery");
                return;
            }
            
            $success = $deliveryService->sendEmail($this->emailFilePath, $this->sender, $this->recipients);
            
            if ($success) {
                Log::info("Email delivery completed successfully");
            }
            
        } catch (\Exception $e) {
            Log::error("Email delivery failed: " . $e->getMessage());
            // Don't throw - email is still saved to Maildir
        }
    }
    
    private function moveEmailToCur(): void
    {
        $curDir = storage_path('app/maildir/cur');
        if (!is_dir($curDir)) {
            mkdir($curDir, 0777, true);
        }
        
        $filename = basename($this->emailFilePath) . ':2,S';
        $curPath = $curDir . '/' . $filename;
        
        if (rename($this->emailFilePath, $curPath)) {
            Log::info("Email moved to cur: {$curPath}");
        } else {
            Log::error("Failed to move email to cur: {$curPath}");
            throw new EmailFileNotFoundException("Failed to move email to cur: {$curPath}");
        }
    }
}
