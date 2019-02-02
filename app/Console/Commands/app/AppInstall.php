<?php

namespace App\Console\Commands\App;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the application for local development';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "Creating database ...\n";
        fclose(fopen(database_path() . '/database.sqlite', 'w'));

        echo "Generating application key ...\n";
        Artisan::call('key:generate');

        echo "Running migrations ...\n";
        Artisan::call('migrate');

        echo "Seeding database 1/2 ...\n";
        Artisan::call('db:seed', ['--class' => 'VoyagerDatabaseSeeder']);

        echo "Seeding database 2/2 ...\n";
        Artisan::call('db:seed');

        echo "Linking storage ...\n";
        Artisan::call('storage:link');

        echo "Generating Passport keys ...\n";
        Artisan::call('passport:install');

        echo "Compiling front-end code ...\n";
        exec('npm run dev');

        // TODO: Copy .env.development to .env, replace DB_DATABASE path

        echo "Please check above if there were any errors.\n";
        echo "If there were no errors, the application is ready for local development!\n";
        echo "\n";
        echo "Administrator credentials:\n";
        echo "Email:    admin@admin.com\n";
        echo "Password: password\n";
    }
}