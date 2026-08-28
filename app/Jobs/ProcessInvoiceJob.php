<?php

namespace App\Jobs;

class ProcessInvoiceJob
{
    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        // Heavy task simulation (for example: Generate PDF Invoice & Send Email)
        \OpenSwoole\Coroutine::sleep(2);

        echo "[JOB FINISHED] Invoice #{$this->payload['invoice_id']} berhasil diproses!\n";
    }
}
