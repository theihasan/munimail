<?php

namespace Modules\SMTP\Services;

use React\EventLoop\Loop;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;
use Illuminate\Support\Facades\Log;

class SmtpClientService
{
    public function __construct(
        private readonly int $timeout = 10,
        private readonly array $tlsOptions = []
    ) {
    }

    /**
     * Get timeout value from configuration
     */
    private function getTimeout(): int
    {
        return (int) config('smtp.client.timeout', $this->timeout);
    }

    /**
     * Get TLS options from configuration
     */
    private function getTlsOptions(): array
    {
        return config('smtp.client.tls', [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]);
    }

    /**
     * Send email directly to MX server
     */
    public function sendToMxServer(string $mxHost, string $mxIp, string $sender, string $recipient, string $emailContent): bool
    {
        try {
            Log::info("Connecting to MX server {$mxHost} ({$mxIp}:25)");
            
            $connection = $this->connectToServer($mxIp, 25);
            if (!$connection) {
                throw new \Exception("Failed to connect to {$mxHost}");
            }

            // SMTP conversation
            $this->performSmtpConversation($connection, $mxHost, $sender, $recipient, $emailContent);
            
            $connection->close();
            Log::info("Email delivered successfully to {$mxHost}");
            
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to deliver email to {$mxHost}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Connect to SMTP server
     */
    private function connectToServer(string $ip, int $port): ?ConnectionInterface
    {
        $connector = new Connector([
            'timeout' => $this->getTimeout(),
            'tls' => $this->getTlsOptions()
        ]);

        $connection = null;
        $connected = false;
        $error = null;

        $connector->connect("{$ip}:{$port}")
            ->then(
                function (ConnectionInterface $conn) use (&$connection, &$connected) {
                    $connection = $conn;
                    $connected = true;
                },
                function (\Exception $e) use (&$error, &$connected) {
                    $error = $e;
                    $connected = true;
                }
            );

        // Wait for connection
        $startTime = time();
        while (!$connected && (time() - $startTime) < $this->getTimeout()) {
            Loop::get()->run();
        }

        if ($error) {
            Log::error("Connection failed: " . $error->getMessage());
            return null;
        }

        return $connection;
    }

    /**
     * Perform SMTP conversation
     */
    private function performSmtpConversation(ConnectionInterface $connection, string $mxHost, string $sender, string $recipient, string $emailContent): void
    {
        $response = $this->sendCommand($connection, "", "220"); // Wait for greeting
        Log::info("Server greeting: " . trim($response));

        // EHLO
        $response = $this->sendCommand($connection, "EHLO " . gethostname(), "250");
        Log::info("EHLO response: " . trim($response));

        // MAIL FROM
        $response = $this->sendCommand($connection, "MAIL FROM:<{$sender}>", "250");
        Log::info("MAIL FROM response: " . trim($response));

        // RCPT TO
        $response = $this->sendCommand($connection, "RCPT TO:<{$recipient}>", "250");
        Log::info("RCPT TO response: " . trim($response));

        // DATA
        $response = $this->sendCommand($connection, "DATA", "354");
        Log::info("DATA response: " . trim($response));

        // Send email content
        $response = $this->sendCommand($connection, $emailContent . "\r\n.", "250");
        Log::info("Email content response: " . trim($response));

        // QUIT
        $response = $this->sendCommand($connection, "QUIT", "221");
        Log::info("QUIT response: " . trim($response));
    }

    /**
     * Send command and wait for response
     */
    private function sendCommand(ConnectionInterface $connection, string $command, string $expectedCode): string
    {
        $response = "";
        $received = false;
        $error = null;

        // Set up data listener
        $connection->on('data', function ($data) use (&$response, &$received, $expectedCode) {
            $response .= $data;
            
            // Check if we have a complete response
            if (preg_match("/^{$expectedCode}/m", $response) || 
                (str_contains($response, "\r\n") && !str_contains($response, "\r\n" . $expectedCode))) {
                $received = true;
            }
        });

        $connection->on('error', function (\Exception $e) use (&$error, &$received) {
            $error = $e;
            $received = true;
        });

        // Send command if provided
        if (!empty($command)) {
            $connection->write($command . "\r\n");
        }

        // Wait for response
        $startTime = time();
        while (!$received && (time() - $startTime) < $this->getTimeout()) {
            Loop::get()->run();
        }

        if ($error) {
            throw new \Exception("SMTP command failed: " . $error->getMessage());
        }

        return $response;
    }
} 