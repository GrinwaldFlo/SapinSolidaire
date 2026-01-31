<?php

namespace App\Console\Commands;

use App\Models\EmailToken;
use Illuminate\Console\Command;

class CleanExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired email tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = EmailToken::cleanExpired();

        $this->info("Cleaned up {$count} expired token(s).");

        return Command::SUCCESS;
    }
}
