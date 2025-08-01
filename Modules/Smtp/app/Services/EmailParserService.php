<?php

namespace Modules\SMTP\Services;

class EmailParserService
{
    /**
     * Parse email content and extract metadata
     */
    public function parseEmail(string $rawEmail): array
    {
        // Split headers and body
        $parts = explode("\r\n\r\n", $rawEmail, 2);
        $headers = $parts[0] ?? '';
        $body = $parts[1] ?? '';

        // Parse headers
        $parsedHeaders = $this->parseHeaders($headers);
        
        // Extract subject
        $subject = $parsedHeaders['subject'] ?? '';
        
        // Extract message ID
        $messageId = $parsedHeaders['message-id'] ?? '';
        
        // Extract in-reply-to
        $inReplyTo = $parsedHeaders['in-reply-to'] ?? '';
        
        // Generate preview (first 500 chars of body)
        $preview = $this->generatePreview($body);
        
        // Extract attachments info
        $attachments = $this->extractAttachmentsInfo($rawEmail);
        
        return [
            'subject' => $subject,
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'headers' => $parsedHeaders,
            'preview' => $preview,
            'attachments' => $attachments,
        ];
    }

    /**
     * Parse email headers into associative array
     */
    private function parseHeaders(string $headers): array
    {
        $parsed = [];
        $lines = explode("\r\n", $headers);
        
        $currentHeader = '';
        $currentValue = '';
        
        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z0-9\-]+):\s*(.*)$/', $line, $matches)) {
                // Save previous header if exists
                if ($currentHeader) {
                    $parsed[strtolower($currentHeader)] = trim($currentValue);
                }
                
                // Start new header
                $currentHeader = $matches[1];
                $currentValue = $matches[2];
            } elseif (preg_match('/^\s+(.+)$/', $line, $matches)) {
                // Continuation of previous header
                $currentValue .= ' ' . $matches[1];
            }
        }
        
        // Save last header
        if ($currentHeader) {
            $parsed[strtolower($currentHeader)] = trim($currentValue);
        }
        
        return $parsed;
    }

    /**
     * Generate preview from email body
     */
    private function generatePreview(string $body): string
    {
        // Remove HTML tags for text preview
        $textBody = strip_tags($body);
        
        // Remove extra whitespace
        $textBody = preg_replace('/\s+/', ' ', $textBody);
        
        // Truncate to 500 characters
        return substr(trim($textBody), 0, 500);
    }

    /**
     * Extract attachment information from email
     */
    private function extractAttachmentsInfo(string $rawEmail): array
    {
        $attachments = [];
        
        // Simple MIME boundary detection
        if (preg_match('/boundary="([^"]+)"/', $rawEmail, $matches)) {
            $boundary = $matches[1];
            $parts = explode("--{$boundary}", $rawEmail);
            
            foreach ($parts as $part) {
                if (preg_match('/Content-Disposition:\s*attachment[^;]*;\s*filename="([^"]+)"/i', $part, $matches)) {
                    $filename = $matches[1];
                    $size = strlen($part);
                    
                    $attachments[] = [
                        'filename' => $filename,
                        'size' => $size,
                        'type' => $this->extractContentType($part),
                    ];
                }
            }
        }
        
        return $attachments;
    }

    /**
     * Extract content type from MIME part
     */
    private function extractContentType(string $part): string
    {
        if (preg_match('/Content-Type:\s*([^;\r\n]+)/i', $part, $matches)) {
            return trim($matches[1]);
        }
        
        return 'application/octet-stream';
    }
} 