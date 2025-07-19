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
    }

    private function setupConnectionHandler(SocketServer $socket, LoggerInterface $logger): void
    {
        $socket->on('connection', function(ConnectionInterface $connection) use($logger){
            $remoteAddress = $connection->getRemoteAddress();
            $this->info("New connection from {$remoteAddress}");

               $connection->state = (object)
                [
                    'messageData' => '',
                    'isAuthenticated' => false,
                    'isTlsActive' => str_starts_with($connection->getRemoteAddress(), 'tls://'),
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
        
    }
}
