<?php

namespace Modules\SMTP\Console;

use Exception;
use React\EventLoop\Loop;
use Psr\Log\LoggerInterface;
use React\Socket\SocketServer;
use Illuminate\Console\Command;
use React\Socket\ConnectionInterface;
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
                $tlsContext = [
                    'local_cert' => $certPath,
                    'local_pk' => $keyPath,
                    'verify_peer' => false
                ];
                $tlsSocket = new SocketServer("tls:://0.0.0.0:{$tlsPort}", $tlsContext, $loop);
                $this->info("Listening for secure SMTP on tls://0.0.0.0:{$tlsPort}");
                $this->setupConnectionHandler($tlsSocket, $logger);
            }catch(Exception $e) {
                $this->error("Could not start TLS SMTP server: ". $e->getMessage());
                return Command::FAILURE;
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

               $connection->state = (object)
                [
                    'buffer' => '',
                    'messageData' => '',
                    'isAuthenticated' => false,
                    'isTlsActive' => str_starts_with($connection->getRemoteAddress(), 'tls://'),
                    'fsmState' => 'INITIAL',
                ]; 

            $this->connection[$remoteAddress] = $connection;

            $connection->write("220 custom.smtp.server ESMTP\r\n");

            $connection->on('data', function($chunk) use($connection, $logger) {
                $connection->state->buffer .= $chunk; 
                $this->processBuffer($connection, $logger);
            });

            $connection->on('close', function() use($connection, $logger, $remoteAddress) {
                $this->info("Connection from {$remoteAddress} closed");
                unset($this->connection[$remoteAddress]);
            });

            $connection->on('error', function(Exception $e) use($connection, $logger, $remoteAddress) {
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
            if(str_contains($state->buffer, "\r\n.\r\n")) {
                list($messagePart, $rest) = explode("\r\n.\r\n", $state->buffer, 2);
                $state->messageData .= $messagePart;
                $state->buffer = $rest;
                $state->fsmState = "HELLO_RECEIVED";
                $connection->write("250 OK: Message accepted\r\n");
            }
            return;
        }
    
        // Process commands line by line
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
            'VRFY, EXPN' => $this->handleVrfyCommand($connection, $args),
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
            $connection->write("503 Error: need MAIL command\r\n");
            return;
        }
        
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
        
        if($state->fsmState !==  'HELO_RECEIVED') {
            $connection->write("503 Bad sequence of commands or TLS already active\r\n");
            return;
        }

        $connection->write("220 TLS go ahead\r\n");

        $connection->startTls()->then(function () use ($connection, $state) {
            $state->isTlsActive = true;
            $connection->write("250 Ready to start TLS\r\n");
            $state->fsmState = 'INITIAL'; // Reset FSM to expect EHLO/HELO again over TLS
            $connection->state->isAuthenticated = false; // Reset auth state after TLS
        }, function (\Exception $e) use ($connection, $state) {
            $this->error("TLS handshake failed for {$connection->getRemoteAddress()}: ". $e->getMessage());
            $connection->end("550 TLS handshake failed\r\n");
            $connection->close();
        });
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
                $decodedData = base64_decode($data);
                $parts = explode("\0", $decodedData);

                if(count($parts) === 3) {
                    $username = $parts[0] ?? '';
                    $password = $parts[1] ?? '';
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
                $password = base64_decode($data);
                $this->authenticateUser($connection, $username, $password);
            });
        });

    }

    private function authenticateUser(ConnectionInterface $connection, string $username, string $password)
    {
        //Currently going with mock but later it will be implemented using async mysql(https://github.com/friends-of-reactphp/mysql)
        if($username = "theihasan" && $password = "123456") {
            $connection->state->isAuthenticated = true;
            $connection->write("235 2.7.0 Authentication successful\r\n");
        } else {
            $connection->write("535 5.7.8 Authentication credentials invalid\r\n");
            $connection->state->isAuthenticated = false;
            $this->warn("Authentication failed for {$connection->getRemoteAddress()} with username {$username} and password {$password}");
        }
    }

}