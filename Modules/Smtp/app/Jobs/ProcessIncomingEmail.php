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
        // Here Email headers will be parsed, attachments will be extracted, and then it will be stored in database using  async mysql and apply content filtering
        
        Log::info("Processing email from {$this->sender} to " . implode(', ', $this->recipients) . " (Size: {$this->emailSize} bytes)");
        
        $this->moveEmailToCur();
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
