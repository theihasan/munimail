<?php

namespace Modules\SMTP\Console;

use Exception;
use React\EventLoop\Loop;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use React\Socket\SocketServer;
use Illuminate\Console\Command;
use React\Socket\ConnectionInterface;
use Modules\SMTP\Jobs\ProcessIncomingEmail;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SmtpServerCommand extends Command
{
    protected $signature = 'smtp:serve {--port=25 : The port to listen on} {--tls-port=587 : The TLS port to listen on} {--cert= : Path to TLS certificate (PEM)} {--key= : Path to TLS private key (PEM)}';
    protected $description = 'Starts a custom SMTP server using ReactPHP.';
    private array $connection = [];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(LoggerInterface $logger) 
    {
        $this->info('Starting SMTP');
        $port = (int) $this->option('port');
        $tlsPort = (int)$this->option('tls-port');
        $certPath = $this->option('cert');
        $keyPath = $this->option('key');

        $loop = Loop::get();

        try {
            $socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
            $this->info("Listening for plaintext SMTP on tcp://0.0.0.0:{$port}");
            $this->setupConnectionHandler($socket, $logger);
        } catch(Exception $e) {
            $this->error("Could not start plaintext SMTP server: ". $e->getMessage());
            return Command::FAILURE;
        }

        if($certPath && $keyPath) {
            try {
                if (!file_exists($certPath)) {
                    throw new Exception("Certificate file not found: {$certPath}");
                }
                if (!file_exists($keyPath)) {
                    throw new Exception("Private key file not found: {$keyPath}");
                }
                
                // ReactPHP TLS context configuration
                $tlsContext = [
                    'tls' => [
                        'local_cert' => $certPath,
                        'local_pk' => $keyPath,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                        'crypto_method' => STREAM_CRYPTO_METHOD_TLS_SERVER
                    ]
                ];
                
                $this->info("Starting TLS SMTP server on port {$tlsPort}...");
                $tlsSocket = new SocketServer("tls://0.0.0.0:{$tlsPort}", $tlsContext, $loop);
                $this->info("Listening for secure SMTP on tls://0.0.0.0:{$tlsPort}");
                $this->setupConnectionHandler($tlsSocket, $logger);
            }catch(Exception $e) {
                $this->error("Could not start TLS SMTP server: ". $e->getMessage());
                $this->warn("Trying minimal TLS setup...");
                
                try {
                    $minimalTlsContext = [
                        'tls' => [
                            'local_cert' => $certPath,
                            'local_pk' => $keyPath,
                            'allow_self_signed' => true
                        ]
                    ];
                    $tlsSocket = new SocketServer("tls://0.0.0.0:{$tlsPort}", $minimalTlsContext, $loop);
                    $this->info("Listening for secure SMTP on tls://0.0.0.0:{$tlsPort} (minimal mode)");
                    $this->setupConnectionHandler($tlsSocket, $logger);
                } catch (Exception $fallbackE) {
                    $this->error("TLS minimal setup also failed: " . $fallbackE->getMessage());
                    return Command::FAILURE;
                }
            }
        } else {
            $this->warn("TLS certificate and key not provided. TLS SMTP server will not start on port {$tlsPort}.");
        }
        $this->info('SMTP server started. Press Ctrl+C to stop.');
        $loop->run();
    }

    private function setupConnectionHandler(SocketServer $socket, LoggerInterface $logger): void
    {
        $socket->on('connection', function(ConnectionInterface $connection) use($logger){
            $remoteAddress = $connection->getRemoteAddress();
            $this->info("New connection from {$remoteAddress}");

            $connection->state = (object)[
                'buffer' => '',
                'isAuthenticated' => false,
                'isTlsActive' => str_starts_with($connection->getRemoteAddress(), 'tls://'),
                'fsmState' => 'INITIAL',
                'sender' => null,
                'recipients' => [],
                'tempFilePath' => null,
                'tempFileHandle' => null,
                'bytesReceived' => 0,
                'maxEmailSize' => 10 * 1024 * 1024, // 10MB limit
            ]; 

            $this->connection[$remoteAddress] = $connection;
            $connection->write("220 custom.smtp.server ESMTP\r\n");

            $connection->on('data', function($chunk) use($connection, $logger) {
                $connection->state->buffer .= $chunk; 
                $this->processBuffer($connection, $logger);
            });

            $connection->on('close', function() use($connection, $logger, $remoteAddress) {
                $this->cleanupTempFile($connection);
                $this->info("Connection from {$remoteAddress} closed");
                unset($this->connection[$remoteAddress]);
            });

            $connection->on('error', function(Exception $e) use($connection, $logger, $remoteAddress) {
                $this->cleanupTempFile($connection);
                $this->error("Connection error from {$remoteAddress} ". $e->getMessage());
            });
        });

        $socket->on('error', function(Exception $e) {
            $this->error('Socket server error ' . $e->getMessage());
        });

    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }

    private function processBuffer(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $state = $connection->state;
    
        if($state->fsmState === 'RECEIVING_DATA') {
            $this->processEmailData($connection, $logger);
            return;
        }
    
        while(str_contains($state->buffer, "\r\n")) {
            list($line, $rest) = explode("\r\n", $state->buffer, 2);
            $state->buffer = $rest;
                
            $this->info("Processing command: '{$line}'");
            
            $this->handleCommand($connection, $line, $logger); 
    
            if($state->fsmState === "RECEIVING_DATA") {
                break;
            }
        }
    }

    private function handleCommand(ConnectionInterface $connection, string $line, LoggerInterface $logger)
    {
        $state = $connection->state;
        $parts = explode(' ', $line, 2);
        $command = strtoupper($parts[0]);
        $args = isset($parts[1]) ? $parts[1] : '';

        $this->info("Received command: {$command} with args: '{$args}' from {$connection->getRemoteAddress()} in state {$state->fsmState}");

        return match($command) {
            'EHLO', 'HELO' => $this->handleEhloCommand($connection, $args, $command),
            'MAIL' => $this->handleMailCommand($connection, $args),
            'RCPT' => $this->handleRcptCommand($connection, $args),
            'DATA' => $this->handleDataCommand($connection),
            'QUIT' => $this->handleQuitCommand($connection),
            'RSET' => $this->handleRsetCommand($connection),
            'NOOP' => $this->handleNoopCommand($connection),
            'AUTH' => $this->handleAuthCommand($connection, $args),
            'STARTTLS' => $this->handleStarttlsCommand($connection),
            'VRFY' => $this->handleVrfyCommand($connection, $args),
            'EXPN' => $this->handleVrfyCommand($connection, $args),
            default => $connection->write("500 Error: {$command} not implemented\r\n")

        };
    }

    private function handleEhloCommand(ConnectionInterface $connection, string $domain, string $command)
    {
        $state = $connection->state;
    
        // Fix: Allow INITIAL state
        if($state->fsmState !== "INITIAL" && $state->fsmState !== "HELLO_RECEIVED") {
            $connection->write("503 Error: bad sequence of commands\r\n");
            return;
        }

        $state->fsmState = "HELLO_RECEIVED"; 
        $state->sender = null;
        $state->recipients = [];
        $state->messageData = '';
    
        $response = "250-{$domain} Hello {$connection->getRemoteAddress()}\r\n";
    
        if($command === "EHLO") {
            // Remove this line: dd($connection->getRemoteAddress());
            $response.= "250-PIPELINING\r\n";
            $response.= "250-SIZE 10485760\r\n";
    
            if(! $state->isTlsActive) {
                $response.= "250-STARTTLS\r\n";
            }
    
            $response.= "250-AUTH PLAIN LOGIN\r\n"; 
            $response.= "250 HELP\r\n";
        } else {
            $response.= "250 OK\r\n";
        }
    
        $connection->write($response);
    }

    private function handleMailCommand(ConnectionInterface $connection, string $args)
    {
        $state = $connection->state;
       
        if($state->fsmState !== 'INITIAL' && $state->fsmState !== 'HELLO_RECEIVED') {
            $connection->write("503 Error: bad sequence of commands\r\n");
            return;
        }

        if(!preg_match('/^FROM:\s*<([^>]+)>$/i', $args, $matches)) {
            $connection->write("501 Syntax error in parameter or arguments\r\n");
            return;
        }

        $sender = $matches[1];

        //Email verification. Currently using just a simple regex. In future MX record look up will be implemented.
        if(! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $connection->write("553 <{$sender}>: Sender address rejected: Malformed address\r\n");
            return;
        }

        $state->sender = $sender;
        $state->recipients = [];
        $state->messageData = '';
        $state->fsmState = 'MAIL_FROM_RECEIVED';
        $connection->write("250 OK: Sender {$state->sender} accepted\r\n");
    }

    private function handleRcptCommand(ConnectionInterface $connection, string $args)
    {
        $state = $connection->state;

        if($state->fsmState !== 'MAIL_FROM_RECEIVED') {
            $connection->write("503 Error: bad sequence of commands\r\n");
            return;
        }

        if(!preg_match('/^TO:\s*<([^>]+)>$/i', $args, $matches)) {
            $connection->write("501 Syntax error in parameter or arguments\r\n");
            return;
        }

        $recipient = $matches[1];

        //Currently go with simple validation. In future it will be implemented.
        if(! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $connection->write("553 <{$recipient}>: Recipient address rejected: Malformed address\r\n");
            return;
        }

        if (isset($state->sender) && strtolower($recipient) === strtolower($state->sender)) {
            $connection->write("553 <{$recipient}>: Recipient address rejected: Sender and recipient cannot be the same\r\n");
            return;
        }

        $state->recipients[] = $recipient;
        $state->fsmState = 'RCPT_TO_RECEIVED';
        $connection->write("250 OK: Recipient {$recipient} accepted\r\n");

    }

    private function handleDataCommand(ConnectionInterface $connection, LoggerInterface $logger = null)
    {
        $state = $connection->state;
        
        if($state->fsmState !== "RCPT_TO_RECEIVED") {
            $connection->write("503 Error: need RCPT command\r\n");
            return;
        }
        
        $tempFilePath = $this->createTempFile();
        $tempFileHandle = fopen($tempFilePath, 'w');
        
        if (!$tempFileHandle) {
            $connection->write("451 Temporary failure: Unable to create temp file\r\n");
            return;
        }
        
        $state->tempFilePath = $tempFilePath;
        $state->tempFileHandle = $tempFileHandle;
        $state->bytesReceived = 0;
        $state->fsmState = "RECEIVING_DATA";
        
        $connection->write("354 Start mail input; end with <CRLF>.<CRLF>\r\n");
    }

    private function handleQuitCommand(ConnectionInterface $connection)
    {
        $connection->write("221 Bye\r\n");
        $connection->end();
    }

    private function handleRsetCommand(ConnectionInterface $connection)
    {
        $state = $connection->state;
        $state->fsmState = 'HELLO_RECEIVED';
        $state->sender = '';
        $state->recipients = [];
        $state->messageData = '';
        $connection->write("250 OK: Reset\r\n");
    }

    private function handleStarttlsCommand(ConnectionInterface $connection)
    {
        $state = $connection->state;
        
        if($state->fsmState !== 'HELLO_RECEIVED') {
            $connection->write("503 Bad sequence of commands or TLS already active\r\n");
            return;
        }

        if($state->isTlsActive) {
            $connection->write("503 TLS already active\r\n");
            return;
        }

        $connection->write("220 Ready to start TLS\r\n");

        $connection->startTls()->then(function () use ($connection, $state) {
            $state->isTlsActive = true;
            $state->fsmState = 'INITIAL'; // Reset FSM to expect EHLO/HELO again over TLS
            $state->isAuthenticated = false; // Reset auth state after TLS
            $this->info("TLS handshake successful for {$connection->getRemoteAddress()}");
        }, function (\Exception $e) use ($connection, $state) {
            $this->error("TLS handshake failed for {$connection->getRemoteAddress()}: ". $e->getMessage());
            $connection->end("550 TLS handshake failed\r\n");
            $connection->close();
        });
    }

    private function handleNoopCommand(ConnectionInterface $connection)
    {
        $connection->write("250 OK\r\n");
    }

    private function handleVrfyCommand(ConnectionInterface $connection, string $args)
    {
        $connection->write("252 OK\r\n");
    }

    private function handleAuthCommand(ConnectionInterface $connection, string $args)
    {
        $state = $connection->state;

        if(! $state->isTlsActive) {
            $connection->write("503 Error: TLS required for authentication\r\n");
            return;
        }

        if($state->fsmState !== 'HELLO_RECEIVED') {
            $connection->write("503 Error: bad sequence of commands\r\n");
            return;
        }

        $parts = explode(' ', $args);
        $mechanism = strtoupper($parts[0] ?? '');

        return match($mechanism){
            'PLAIN' => $this->handlePlainAuth($connection, $parts[1] ?? ''),
            'LOGIN' => $this->handleLoginAuth($connection),
            default => $connection->write("500 Error: Authentication mechanism not supported\r\n")
        };

    }

    private function handlePlainAuth(ConnectionInterface $connection, string $initialResponse)
    {
        $state = $connection->state;

        if($state->fsmState !== 'HELLO_RECEIVED') {
            $connection->write("503 Error: bad sequence of commands\r\n");
            return;
        }

        if($initialResponse) {
            $decodeResponse = base64_decode($initialResponse);
            // Format: authorization identity (optional) + NUL + authentication identity + NUL + password
            // Example: \0username\0password

            $parts = explode("\0", $decodeResponse);

            if(count($parts) === 3) {
                $username = $parts[1] ?? '';
                $password = $parts[2] ?? '';

                $this->authenticateUser($connection, $username, $password);
            } else {
                $connection->write("535 5.7.8 Authentication credentials invalid\r\n");
            }
        } else {
            $connection->write("334\r\n");
            $connection->once('data', function ($data) use($connection) {
                $decodedData = base64_decode(trim($data));
                $parts = explode("\0", $decodedData);

                if(count($parts) === 3) {
                    $username = $parts[1] ?? '';
                    $password = $parts[2] ?? '';
                    $this->authenticateUser($connection, $username, $password);
                } else {
                    $connection->write("535 5.7.8 Authentication credentials invalid\r\n");
                }
            });
        }
        
    }

    private function handleLoginAuth(ConnectionInterface $connection)
    {
        $state = $connection->state;
        $connection->write("334 ". base64_encode("Username:"). "\r\n");

        $connection->once('data', function ($data) use($connection) {
            $username = base64_decode(trim($data));
            $connection->write("334 ". base64_encode("Password:"). "\r\n");
            $connection->once('data', function ($data) use($connection, $username) {
                $password = base64_decode(trim($data));
                $this->authenticateUser($connection, $username, $password);
            });
        });

    }

    private function authenticateUser(ConnectionInterface $connection, string $username, string $password)
    {
        //Currently going with mock but later it will be implemented using async mysql(https://github.com/friends-of-reactphp/mysql)
        if($username == "theihasan" && $password == "123456") {
            $connection->state->isAuthenticated = true;
            $connection->write("235 2.7.0 Authentication successful\r\n");
        } else {
            $connection->write("535 5.7.8 Authentication credentials invalid\r\n");
            $connection->state->isAuthenticated = false;
            $this->warn("Authentication failed for {$connection->getRemoteAddress()} with username {$username} and password {$password}");
        }
    }

    private function storeEmail(string $rawEmail, ?string $sender, array $recipients): void
    {
        $maildirRoot = storage_path('app/maildir');
        $tmpDir = $maildirRoot . '/tmp';
        $newDir = $maildirRoot . '/new';
        $curDir = $maildirRoot . '/cur';
    
        if (!is_dir($tmpDir)) { mkdir($tmpDir, 0777, true); }
        if (!is_dir($newDir)) { mkdir($newDir, 0777, true); }
        if (!is_dir($curDir)) { mkdir($curDir, 0777, true); }
    
        // Generate a unique filename based on Maildir spec (timestamp.pid.hostname.random)
        $uniqueId = time() . '.' . getmypid() . '.' . gethostname() . '.' . Str::random(8);
        $tmpFilePath = $tmpDir . '/' . $uniqueId;
        $newFilePath = $newDir . '/' . $uniqueId;
    
        try {
            file_put_contents($tmpFilePath, $rawEmail);
    
            rename($tmpFilePath, $newFilePath);
    
            $this->info("Email from {$sender} to " . implode(', ', $recipients) . " saved to Maildir: {$newFilePath}");
    
        } catch (\Exception $e) {
            $this->info("Failed to save email to Maildir: " . $e->getMessage());
            if (file_exists($tmpFilePath)) {
                unlink($tmpFilePath);
            }
        }
    }

    private function processEmailData(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $state = $connection->state;
        
        // Check for end-of-data marker
        if(str_contains($state->buffer, "\r\n.\r\n")) {
            list($finalChunk, $rest) = explode("\r\n.\r\n", $state->buffer, 2);
            
            if (!empty($finalChunk)) {
                if (!$this->writeChunkToFile($connection, $finalChunk)) {
                    return;
                }
            }
            
            $state->buffer = $rest;
            $this->finalizeEmail($connection, $logger);
            return;
        }
        
        // Stream data in chunks
        if (strlen($state->buffer) > 8192) { // 8KB chunks
            $chunk = substr($state->buffer, 0, 8192);
            $state->buffer = substr($state->buffer, 8192);
            
            if (!$this->writeChunkToFile($connection, $chunk)) {
                return; 
            }
        }
    }

    private function writeChunkToFile(ConnectionInterface $connection, string $chunk): bool
    {
        $state = $connection->state;
        
        if ($state->bytesReceived + strlen($chunk) > $state->maxEmailSize) {
            $this->cleanupTempFile($connection);
            $connection->write("552 Message size exceeds maximum allowed size\r\n");
            $state->fsmState = 'HELLO_RECEIVED';
            return false;
        }
        
        // Write to file
        $bytesWritten = fwrite($state->tempFileHandle, $chunk);
        if ($bytesWritten === false) {
            $this->cleanupTempFile($connection);
            $connection->write("451 Temporary failure: Unable to write email data\r\n");
            $state->fsmState = 'HELLO_RECEIVED';
            return false;
        }
        
        $state->bytesReceived += $bytesWritten;
        return true;
    }

    private function finalizeEmail(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $state = $connection->state;
        
        if ($state->tempFileHandle) {
            fclose($state->tempFileHandle);
            $state->tempFileHandle = null;
        }
        
        $success = $this->moveEmailToMaildir(
            $state->tempFilePath, 
            $state->sender, 
            $state->recipients
        );
        
        if ($success) {
            $connection->write("250 OK: Message accepted\r\n");
            
            $uniqueId = basename($state->tempFilePath, '.tmp');
            $finalPath = storage_path('app/maildir/new/' . $uniqueId);
            
           ProcessIncomingEmail::dispatch(
                $finalPath,
                $state->sender,
                $state->recipients,
                $state->bytesReceived
            );
            
            $this->info("Email from {$state->sender} ({$state->bytesReceived} bytes) saved to Maildir and queued for processing");
        } else {
            $connection->write("451 Temporary failure: Unable to save email\r\n");
        }
        
        $state->fsmState = 'HELLO_RECEIVED';
        $state->sender = null;
        $state->recipients = [];
        $state->tempFilePath = null;
        $state->bytesReceived = 0;
    }

    private function createTempFile(): string
    {
        $tempDir = storage_path('app/maildir/tmp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $uniqueId = time() . '.' . getmypid() . '.' . gethostname() . '.' . Str::random(8);
        return $tempDir . '/' . $uniqueId . '.tmp';
    }

    private function moveEmailToMaildir(string $tempFilePath, ?string $sender, array $recipients): bool
    {
        try {
            $maildirRoot = storage_path('app/maildir');
            $newDir = $maildirRoot . '/new';
            
            if (!is_dir($newDir)) {
                mkdir($newDir, 0777, true);
            }
            
            $uniqueId = basename($tempFilePath, '.tmp');
            $finalPath = $newDir . '/' . $uniqueId;
            
            return rename($tempFilePath, $finalPath);
            
        } catch (\Exception $e) {
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            return false;
        }
    }

    private function cleanupTempFile(ConnectionInterface $connection)
    {
        $state = $connection->state;
        
        if ($state->tempFileHandle) {
            fclose($state->tempFileHandle);
            $state->tempFileHandle = null;
        }
        
        if ($state->tempFilePath && file_exists($state->tempFilePath)) {
            unlink($state->tempFilePath);
            $state->tempFilePath = null;
        }
    }
}