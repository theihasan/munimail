<?php

namespace Modules\SMTP\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SmtpServerCommand extends Command
{
    protected $signature = 'smtp:serve {--port=25 : The port to listen on} {--tls-port=587 : The TLS port to listen on} {--cert= : Path to TLS certificate (PEM)} {--key= : Path to TLS private key (PEM)}';
    protected $description = 'Starts a custom SMTP server using ReactPHP.';


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
    public function handle() {
        return 'oke';
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
}
