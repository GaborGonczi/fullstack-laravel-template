<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;

class SailSetup extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'fullstack:sail';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Set up a new Laravel project with Sail';


  public function __construct()
  {
    parent::__construct();

  }

  /**
   * Execute the console command.
   */
  public function handle()
  {

    $this->info('Laravel Sail setup...');

    if (!file_exists(base_path('docker-compose.yml'))) {
      $this->call('sail:install');
    }

    $this->info('Sail installed successfully ');

    $this->info('Create devscript');

    $this->createDevScript();

    $this->info('Start Sail container...');

    $process = new Process(['./bin/dev', 'start']);

    $process->run();

    $this->info('Sail started successfully. Now running...');

  
  }

  /**
   * Create a development script in the `bin` directory that interacts with Laravel Sail.
   * This script allows the user to start, stop, or restart the Laravel Sail containers.
   * It also ensures that the necessary directories and permissions are in place.
   *
   * @return void
   */
  private function createDevScript()
  {
    $binDirectory = base_path('bin');

    if (!File::exists($binDirectory)) {
      File::makeDirectory($binDirectory, 0755, true);
      $this->info("Created the bin directory.");
    }

    $devScriptPath = $binDirectory . '/dev';

    if (File::exists($devScriptPath)) {
      $this->info('The dev script already exists.');
      return;
    }

    $devScriptContent = <<<SCRIPT
         #!/bin/bash
         
         PROJECT_ROOT=\$(cd "\$(dirname "\$(dirname "\$0")")"; pwd)
         SAIL="\$PROJECT_ROOT/vendor/bin/sail"
         
         if [ ! -f "\$SAIL" ]; then
           echo " Laravel Sail not installed! Run: php artisan fullstack:sail"
           exit 1
         fi
         
         case "\$1" in
           start)
             echo " Starting the container and an interactive shell"
             \$SAIL up -d && \$SAIL shell
             ;;
           stop)
             echo " Shut down sail"
             \$SAIL down
             ;;
           restart)
             echo " Restart sail..."
             \$SAIL down && \$SAIL up -d && \$SAIL shell
             ;;
           *)
             echo "Használat: dev [start|stop|restart]"
             ;;
         esac
         SCRIPT;

    File::put($devScriptPath, $devScriptContent);

    chmod($devScriptPath, 0755);

    $this->info('The dev script has been created and is executable.');
  }

}
