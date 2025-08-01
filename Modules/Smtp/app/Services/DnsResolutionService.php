<?php

namespace Modules\SMTP\Services;

use React\EventLoop\Loop;
use React\Dns\Resolver\Factory as DnsFactory;
use React\Dns\Model\Message;
use Illuminate\Support\Facades\Log;

class DnsResolutionService
{
    public function __construct(
        private readonly string $dnsServer = '8.8.8.8'
    ) {
    }

    /**
     * Resolve MX records for a domain
     */
    public function resolveMxRecords(string $domain): array
    {
        $loop = Loop::get();
        $dnsFactory = new DnsFactory();
        $resolver = $dnsFactory->create($this->getDnsServer(), $loop);
        
        $promise = $resolver->resolveAll($domain, Message::TYPE_MX);
        
        $result = null;
        $error = null;
        
        $promise->then(
            function (array $records) use (&$result) {
                $mxRecords = [];
                $mxRecords = collect($records)->map(function ($record) {
                    if (is_object($record)) {
                        return [
                            'host' => $record->exchange,
                            'priority' => $record->priority,
                        ];
                    } elseif (is_array($record)) {
                        return [
                            'host' => $record['exchange'] ?? $record['target'],
                            'priority' => $record['priority'] ?? 10,
                        ];
                    }
                    return null;
                })->filter()->values()->all();
                
                // Sort by priority (lower is higher priority)
                usort($mxRecords, fn($a, $b) => $a['priority'] <=> $b['priority']);
                
                $result = $mxRecords;
            },
            function (\Exception $e) use (&$error) {
                $error = $e;
            }
        );
        
        // Wait for the promise to resolve
        $loop->run();
        
        if ($error) {
            Log::error("DNS resolution failed for {$domain}: " . $error->getMessage());
            throw new \Exception("DNS resolution failed for {$domain}: " . $error->getMessage());
        }
        
        if (empty($result)) {
            Log::warning("No MX records found for domain: {$domain}");
            throw new \Exception("No MX records found for domain: {$domain}");
        }
        
        Log::info("Resolved MX records for {$domain}: " . json_encode($result));
        return $result;
    }

    /**
     * Get the best MX server for a domain
     */
    public function getBestMxServer(string $domain): string
    {
        $mxRecords = $this->resolveMxRecords($domain);
        
        if (empty($mxRecords)) {
            throw new \Exception("No MX records found for domain: {$domain}");
        }

        // Return the highest priority (lowest number) MX server
        return $mxRecords[0]['host'];
    }

    /**
     * Resolve A records for a hostname
     */
    public function resolveARecords(string $hostname): array
    {
        Log::info("Resolving A records for: {$hostname}");
        
        $loop = Loop::get();
        $dnsFactory = new DnsFactory();
        $resolver = $dnsFactory->create($this->getDnsServer(), $loop);
        
        $promise = $resolver->resolveAll($hostname, Message::TYPE_A);
        
        $result = null;
        $error = null;
        
        $promise->then(
            function (array $records) use (&$result, $hostname) {
                Log::info("Received " . count($records) . " A records for {$hostname}");
                $aRecords = [];
                $aRecords = collect($records)->map(function ($record) {
                    return match (true) {
                        is_string($record) => (function() use ($record) {
                            Log::info("A record (string): " . $record);
                            return $record;
                        })(),
                        is_object($record) => (function() use ($record) {
                            Log::info("A record (object): " . $record->address);
                            return $record->address;
                        })(),
                        is_array($record) => (function() use ($record) {
                            $address = $record['address'] ?? $record['target'];
                            Log::info("A record (array): " . $address);
                            return $address;
                        })(),
                        default => null,
                    };
                })->filter()->values()->all();
                Log::info("Final A records: " . json_encode($aRecords));
                $result = $aRecords;
            },
            function (\Exception $e) use (&$error, $hostname) {
                Log::error("A record resolution error for {$hostname}: " . $e->getMessage());
                $error = $e;
            }
        );
        
        // Wait for the promise to resolve
        $loop->run();
        
        if ($error) {
            Log::error("A record resolution failed for {$hostname}: " . $error->getMessage());
            throw new \Exception("A record resolution failed for {$hostname}: " . $error->getMessage());
        }
        
        if (empty($result)) {
            Log::warning("No A records found for hostname: {$hostname}");
            throw new \Exception("No A records found for hostname: {$hostname}");
        }
        
        Log::info("Resolved A records for {$hostname}: " . implode(', ', $result));
        return $result;
    }

    /**
     * Get IP address for MX server
     */
    public function getMxServerIp(string $mxHost): string
    {
        $aRecords = $this->resolveARecords($mxHost);
        
        if (empty($aRecords)) {
            throw new \Exception("No A records found for MX host: {$mxHost}");
        }

        // Return the first IP address
        return $aRecords[0];
    }

    /**
     * Get DNS server from configuration
     */
    private function getDnsServer(): string
    {
        return config('smtp.dns.server', $this->dnsServer);
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