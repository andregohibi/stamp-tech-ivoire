<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateEncryptionKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-encryption-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $length = (int) $this->option('length');

        $key = bin2hex(random_bytes($length));

        $this->info("Generated encryption key:");
        $this->line($key);

        if($this->option('show')){
            $this->newLine();
            $this->info("For .env file:");
            $this->line("QR_ENCRYPTION_KEY={$key}");
        }
        return Command::SUCCESS;

    }
}
