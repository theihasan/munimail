<?php

declare(strict_types=1);

/**
 * TLS SMTP Server Test Script
 * Tests TLS connection to SMTP server on port 587
 */

class TlsSmtpTester
{
    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 587,
        private readonly int $timeout = 30
    ) {}

    public function testConnection(): bool
    {
        echo "Testing TLS SMTP connection to {$this->host}:{$this->port}\n";
        echo str_repeat('-', 50) . "\n";

        try {
            $socket = $this->createTlsConnection();
            
            if (!$socket) {
                echo "❌ Failed to establish TLS connection\n";
                return false;
            }

            echo "✅ TLS connection established successfully\n";
            
            // Test SMTP protocol
            $this->testSmtpProtocol($socket);
            
            fclose($socket);
            echo "✅ Connection closed successfully\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function createTlsConnection()
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'ciphers' => 'HIGH:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!SRP:!CAMELLIA',
                'disable_compression' => true,
                'capture_peer_cert' => false
            ]
        ]);

        $socket = @stream_socket_client(
            "tls://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new Exception("Failed to connect: {$errstr} ({$errno})");
        }

        return $socket;
    }

    private function testSmtpProtocol($socket): void
    {
        echo "\n📧 Testing SMTP Protocol:\n";
        
        // Read initial greeting
        $greeting = $this->readResponse($socket);
        echo "Server greeting: {$greeting}";
        
        if (!str_starts_with($greeting, '220')) {
            throw new Exception("Invalid greeting: {$greeting}");
        }

        // Test EHLO command
        $this->sendCommand($socket, "EHLO test.example.com");
        $ehloResponse = $this->readResponse($socket);
        echo "EHLO response: {$ehloResponse}";
        
        if (!str_starts_with($ehloResponse, '250')) {
            throw new Exception("EHLO failed: {$ehloResponse}");
        }

        // Test authentication
        $this->testAuthentication($socket);
        
        // Test email sending
        $this->testEmailSending($socket);
        
        // Quit
        $this->sendCommand($socket, "QUIT");
        $quitResponse = $this->readResponse($socket);
        echo "QUIT response: {$quitResponse}";
    }

    private function testAuthentication($socket): void
    {
        echo "\n🔐 Testing Authentication:\n";
        
        // Test AUTH PLAIN
        $credentials = base64_encode("\0theihasan\0123456");
        $this->sendCommand($socket, "AUTH PLAIN {$credentials}");
        $authResponse = $this->readResponse($socket);
        echo "AUTH PLAIN response: {$authResponse}";
        
        if (str_starts_with($authResponse, '235')) {
            echo "✅ Authentication successful\n";
        } else {
            echo "❌ Authentication failed\n";
        }
    }

    private function testEmailSending($socket): void
    {
        echo "\n📮 Testing Email Sending:\n";
        
        // MAIL FROM
        $this->sendCommand($socket, "MAIL FROM:<test@example.com>");
        $mailResponse = $this->readResponse($socket);
        echo "MAIL FROM response: {$mailResponse}";
        
        // RCPT TO
        $this->sendCommand($socket, "RCPT TO:<recipient@example.com>");
        $rcptResponse = $this->readResponse($socket);
        echo "RCPT TO response: {$rcptResponse}";
        
        // DATA
        $this->sendCommand($socket, "DATA");
        $dataResponse = $this->readResponse($socket);
        echo "DATA response: {$dataResponse}";
        
        if (str_starts_with($dataResponse, '354')) {
            // Send email content
            $emailContent = $this->getTestEmailContent();
            fwrite($socket, $emailContent);
            fwrite($socket, "\r\n.\r\n");
            
            $finalResponse = $this->readResponse($socket);
            echo "Email acceptance response: {$finalResponse}";
            
            if (str_starts_with($finalResponse, '250')) {
                echo "✅ Email sent successfully\n";
            } else {
                echo "❌ Email sending failed\n";
            }
        }
    }

    private function sendCommand($socket, string $command): void
    {
        echo "→ {$command}\n";
        fwrite($socket, $command . "\r\n");
    }

    private function readResponse($socket): string
    {
        $response = '';
        while (true) {
            $line = fgets($socket);
            if ($line === false) {
                break;
            }
            
            $response .= $line;
            
            // Check if this is the last line (doesn't have a dash after the code)
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        
        return $response;
    }

    private function getTestEmailContent(): string
    {
        return "From: test@example.com\r\n" .
               "To: recipient@example.com\r\n" .
               "Subject: TLS SMTP Test Email\r\n" .
               "Date: " . date('r') . "\r\n" .
               "\r\n" .
               "This is a test email sent via TLS SMTP connection.\r\n" .
               "Test performed at: " . date('Y-m-d H:i:s') . "\r\n" .
               "\r\n" .
               "If you receive this, TLS SMTP is working correctly!\r\n";
    }

    public function testPlaintextConnection(): bool
    {
        echo "\n🔓 Testing Plaintext Connection (port 25):\n";
        
        try {
            $socket = @stream_socket_client(
                "tcp://127.0.0.1:25",
                $errno,
                $errstr,
                $this->timeout
            );

            if (!$socket) {
                echo "❌ Plaintext connection failed: {$errstr} ({$errno})\n";
                return false;
            }

            echo "✅ Plaintext connection established\n";
            
            // Read greeting and test STARTTLS
            $greeting = $this->readResponse($socket);
            echo "Server greeting: {$greeting}";
            
            $this->sendCommand($socket, "EHLO test.example.com");
            $ehloResponse = $this->readResponse($socket);
            echo "EHLO response: {$ehloResponse}";
            
            if (str_contains($ehloResponse, 'STARTTLS')) {
                echo "✅ STARTTLS capability detected\n";
            }
            
            $this->sendCommand($socket, "QUIT");
            $quitResponse = $this->readResponse($socket);
            
            fclose($socket);
            return true;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    echo "🚀 SMTP TLS Connection Tester\n";
    echo "============================\n\n";
    
    $tester = new TlsSmtpTester();
    
    // Test TLS connection
    $tlsSuccess = $tester->testConnection();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    
    // Test plaintext connection
    $plaintextSuccess = $tester->testPlaintextConnection();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "📊 Test Results Summary:\n";
    echo "TLS Connection (port 587): " . ($tlsSuccess ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "Plaintext Connection (port 25): " . ($plaintextSuccess ? "✅ PASS" : "❌ FAIL") . "\n";
    
    if ($tlsSuccess && $plaintextSuccess) {
        echo "\n🎉 All tests passed! Your SMTP server is working correctly.\n";
        exit(0);
    } else {
        echo "\n⚠️  Some tests failed. Check your SMTP server configuration.\n";
        exit(1);
    }
} else {
    echo "This script must be run from the command line.\n";
}
