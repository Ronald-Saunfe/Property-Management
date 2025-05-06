<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRentDueReminders;
use Illuminate\Console\Command;

class SendRentDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:rent-due {days=5 : Days before due date to send reminder}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send rent due reminders to tenants';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->argument('days');
        
        $this->info("Dispatching rent due reminders job for {$days} days before due date");
        
        // Dispatch the job to process rent due reminders
        ProcessRentDueReminders::dispatch($days);
        
        $this->info('Rent due reminders job dispatched successfully');
        
        return Command::SUCCESS;
    }
}