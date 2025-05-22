<?php

namespace johntrickett86\Junie;

use johntrickett86\Junie\Console\Commands\InstallGuidelinesCommand;
use Illuminate\Support\ServiceProvider;

class JunieServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    public function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallGuidelinesCommand::class,
        ]);
    }

    public function configurePublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/junie.php' => config_path('junie.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/junie.php', 'junie'
        );
    }
}
