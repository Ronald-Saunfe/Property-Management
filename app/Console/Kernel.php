<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule rent due reminders to run daily at 8:00 AM
        $schedule->command('reminders:rent-due')->dailyAt('08:00');
        
        // You can also schedule reminders with different days before due date
        // $schedule->command('reminders:rent-due 3')->dailyAt('08:00'); // 3 days before
        // $schedule->command('reminders:rent-due 1')->dailyAt('08:00'); // 1 day before
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
