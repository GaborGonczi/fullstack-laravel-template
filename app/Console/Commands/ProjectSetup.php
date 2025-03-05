<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;


class ProjectSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fullstack:packages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive project setup command for installing and configuring essential Laravel packages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->addStyles('success', null, null, []);

        $this->addStyles('success', 'bright-white', 'blue', ['bold']);

        $this->initCommand();

        $packages = [
            'Filament' => 'installFilament',
            'Blueprint' => 'installBlueprint',
            'RESTPresenter' => 'installRESTPresenter',
            'Filament Scaffold' => 'installFilamentScaffold',
            'Filament Helpers' => 'installFilamentHelpers',
            'Filament PWA' => 'installFilamentPWA',
            'PHPWord' => 'installPHPWord',
            'mPDF' => 'installMPdf',

        ];
        $docs = [
            'Filament' => "A collection of beautiful full-stack components for Laravel.\nThe perfect starting point for your next app.\nUsing Livewire, Alpine.js and Tailwind CSS.\nFor more info: https://filamentphp.com/docs\n",
            'Blueprint' => "Blueprint is an open-source tool for rapidly generating multiple Laravel components from a single, human readable definition.\nFor more info: https://blueprint.laravelshift.com/docs/getting-started/\n",
            'RESTPresenter' => "Effortless API Creation with Filament Panel, Export Collections, Test Generation and more.\nFor more info: https://filamentphp.com/plugins/adam-lee-rest-presenter/\n",
            'Filament Scaffold' => "Scaffold is a toolkiit that simplifies the generation of resources.\nIt can automatically generate madels, resources, migration files, and more, creating corresponding forms and table views based on the database table.\nFor more info: https://filamentphp.com/plugins/solution-forest-scaffold/\n",
            'Filament Helpers' => "Helper Class Generator to manage your forms and table inside your filament app.\nFor more info: https://filamentphp.com/plugins/3x1io-tomato-helpers/\n",
            'Filament PWA' => "Integrate a PWA feature into your FilamentPHP app with settings accessible from the panel.\nFor more info: https://filamentphp.com/plugins/3x1io-tomato-pwa\n",
            'PHPWord' => "PHPWord is a library written in pure PHP that provides a set of classes to write to different document file formats, i.e. Microsoft Office Open XML(.docx),\n OASIS Open Document Format for Office Applications (.odt),\n Rich Text Format (.rtf),\n Microsoft Word Binary File (.doc),\n HTML (.html),\n and PDF (.pdf).\nFor more info: https://phpoffice.github.io/PHPWord/\n",
            'mPDF' => "mPDF is a PHP library which generates PDF files from UTF-8 encoded HTML.\nFor more info: https://mpdf.github.io/\n",

        ];



        foreach ($docs as $key => $value) {
            $this->info("<fg=white;bg=green><options=bold>$key</></>: $value");
        }

        $selectedPackages = (new \Laravel\Prompts\MultiSelectPrompt(
            'Which packages would you like to install?',
            array_keys($packages)
        ))->prompt();


        foreach ($selectedPackages as $package) {
            $this->info("Migrate before install $package");
            $this->runSailCommand('php', 'artisan', 'migrate');
            $this->{$packages[$package]}();
        }


        $this->publishMigrations();

        $this->runSailCommand('php', 'artisan', 'migrate');

        $this->runSailCommand('php', 'artisan', 'key:generate');



        $this->info('Project setup complete!');
    }

    /**
     * Initialize the command by running migrations and installing the `laravel/prompts` package if it's not installed.
     *
     * @return void
     */
    private function initCommand()
    {
        $this->runSailCommand('php', 'artisan', 'migrate');

        if (!$this->isInstalled('laravel/prompts')) {
            $this->runSailCommand('composer', 'require', 'laravel/prompts');
            $this->runSailCommand('composer', '', 'dump-autoload');
        }
    }

    /**
     * Install the Filament package and its required configurations.
     *
     * @return void
     */
    private function installFilament()
    {
        $this->info('Installing Filament package...');
        if (!$this->isInstalled('filament/filament')) {
            $this->runSailCommand('composer', 'require', 'filament/filament:^3.2', ['-W']);
        }

        $this->runSailCommand('php', 'artisan', 'filament:install', ['--panels']);

        $this->info('Setting language to Hungarian in config...');
        config(['app.locale' => 'hu']);

        $this->info('Publishing Filament config...');
        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--tag=filament-config']);

        $this->info('Publishing Filament panels translations...');
        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--tag=filament-panels-translations']);

        $choice = (new \Laravel\Prompts\ConfirmPrompt(
            'Do you want to create a Filament user now? (default is no)',
            false
        ))->prompt();
        if ($choice) {
            $this->info('Creating Filament user...');
            $this->runSailCommand('php', 'artisan', 'make:filament-user');
        }
    }

    /**
     * Install the Blueprint package and add necessary entries to the .gitignore file.
     *
     * @return void
     */
    private function installBlueprint()
    {
        $this->info('Installing Blueprint package...');
        if (!$this->isInstalled('laravel-shift/blueprint')) {
            $this->runSailCommand('composer', 'require', 'laravel-shift/blueprint', ['-W', '--dev']);
        }
        file_put_contents('.gitignore', "\n/.draft.yaml", FILE_APPEND);
        file_put_contents('.gitignore', "\n/.blueprint", FILE_APPEND);
    }

    /**
     * Install the Filament PWA package and configure the necessary files and migrations.
     *
     * @return void
     */
    private function installFilamentPWA()
    {
        $this->info('Installing Filament PWA package...');
        if (!$this->isInstalled('tomatophp/filament-pwa')) {
            $this->runSailCommand('composer', 'require', 'tomatophp/filament-pwa');
        }

        $this->runSailCommand('php', 'artisan', 'filament-pwa:install');
        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"']);

        $this->installFilamentSettingsHub();

        $this->info('Registering Filament PWA plugin...');
        $adminPanelProviderFile = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));
        $adminPanelProviderFile = str_replace(');', ")\n->plugin(\TomatoPHP\FilamentPWA\FilamentPWAPlugin::make());", $adminPanelProviderFile);
        file_put_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'), $adminPanelProviderFile);

        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--tag="filament-pwa-config"']);
        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--tag="filament-pwa-views"']);
        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--tag="filament-pwa-lang"']);
    }

    /**
     * Install the Filament Settings Hub package.
     *
     * @return void
     */
    private function installFilamentSettingsHub()
    {
        $this->info('Installing Filament Settings Hub...');
        $this->runSailCommand('php', 'artisan', 'filament-settings-hub:install');
    }

    /**
     * Install the PHPWord package.
     *
     * @return void
     */
    private function installPHPWord()
    {
        $this->info('Installing PHPWord package...');
        if (!$this->isInstalled('phpoffice/phpword')) {
            $this->runSailCommand('composer', 'require', 'phpoffice/phpword');
        }
    }

    /**
     * Install the mPDF package.
     *
     * @return void
     */
    private function installMPdf()
    {
        $this->info('Installing mPDF package...');
        if (!$this->isInstalled('mpdf/mpdf')) {
            $this->runSailCommand('composer', 'require', 'mpdf/mpdf');
        }
    }

    /**
     * Install the Filament Helpers package.
     *
     * @return void
     */
    private function installFilamentHelpers()
    {
        $this->info('Installing Filament Helpers package...');
        if (!$this->isInstalled('tomatophp/filament-helpers')) {
            $this->runSailCommand('composer', 'require', 'tomatophp/filament-helpers');
        }
    }

    /**
     * Install the Filament Scaffold package and register its plugin.
     *
     * @return void
     */
    private function installFilamentScaffold()
    {
        $this->info('Installing Filament Scaffold package...');
        if (!$this->isInstalled('solution-forest/filament-scaffold')) {
            $this->runSailCommand('composer', 'require', 'solution-forest/filament-scaffold');
        }

        $this->info('Registering Filament Scaffold plugin...');
        $adminPanelProviderFile = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));
        $adminPanelProviderFile = str_replace(');', ")\n->plugin(\Solutionforest\FilamentScaffold\FilamentScaffoldPlugin::make());", $adminPanelProviderFile);
        file_put_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'), $adminPanelProviderFile);

        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--provider="Solutionforest\FilamentScaffold\FilamentScaffoldServiceProvider" --tag="filament-scaffold-config"']);
    }

    /**
     * Install the RESTPresenter package and run the Filament installation command.
     *
     * @return void
     */
    private function installRESTPresenter()
    {
        $this->info('Installing RESTPresenter package...');
        if (!$this->isInstalled('xtend-packages/rest-presenter')) {
            $this->runSailCommand('composer', 'require', ' xtend-packages/rest-presenter');
        }

        $this->runSailCommand('php', 'artisan', 'rest-presenter:filament', ['--install']);
    }

    /**
     * Publish the necessary migrations for the app.
     *
     * @return void
     */
    private function publishMigrations()
    {
        $this->info('Publishing migrations...');
        $this->runSailCommand('php', 'artisan', 'vendor:publish', ['--provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"']);
    }

    /**
     * Run a command via Sail using the provided executable, command type, and arguments.
     *
     * @param string $executable The executable to run (e.g., 'php', 'composer')
     * @param string $commandType The type of the command (e.g., 'artisan', 'require')
     * @param string $command The specific command to run (e.g., 'migrate', 'filament:install')
     * @param array  $arguments Additional arguments for the command
     * 
     * @return void
     */
    private function runSailCommand($executable, $commandType, $command, $arguments = [])
    {
        $process = new Process(array_filter([
            $executable,
            $commandType,
            $command,
            ...$arguments
        ], function ($arg) {
            return $arg !== '';
        }));

        $process->setTty(true);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('Failed to run command: ' . $process->getCommandLine() . ' ' . $process->getErrorOutput());
            return;
        }

        $process->getOutput();

        $this->line("<success> SUCCESS! </success> Command " . $process->getCommandLine());
    }

    /**
     * Check if a package is installed by looking in the composer.json file.
     *
     * @param string $package The name of the package to check for
     *
     * @return bool True if the package is installed, false otherwise
     */
    private function isInstalled($package)
    {
        $composer = json_decode(file_get_contents('composer.json'), true);

        return array_key_exists($package, $composer['require'] ?? [])
            || array_key_exists($package, $composer['require-dev'] ?? []);
    }

    /**
     * Add a custom style to the output formatter.
     *
     * @param string $name The name of the style
     * @param string $fg The foreground color (text color)
     * @param string $bg The background color
     * @param array  $options Additional options for the style (e.g., 'bold')
     *
     * @return void
     */
    private function addStyles($name, $fg, $bg, $options = [])
    {
        $style = new OutputFormatterStyle($fg, $bg, $options);
        $this->output->getFormatter()->setStyle($name, $style);
    }

}
