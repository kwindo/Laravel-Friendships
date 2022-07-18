<?php


namespace Kimdevylder\Friendships;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class FriendshipsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMigrations();
    }

    /**
     * Register Acquaintances's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $config = $this->app['config']['friendships'];
        $runMigrations = is_null($config['migrations'] ?? null) 
            ? count(\File::glob(database_path('migrations/*friendships*.php'))) === 0
            : $config['migrations'];

        if ($runMigrations) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
    }

    /**
     * Setup the configuration for Acquaintances.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/friendships.php', 'friendships'
        );
    }

    /**
     * Setup the resource publishing groups for friendships.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/config/friendships.php' => config_path('friendships.php'),
            ], 'friendships-config');

            $this->publishes($this->updateMigrationDate(), 'friendships-migrations');
        }
    }


    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @return array
     */
    protected function updateMigrationDate(): array
    {
        $tempArray = [];
        $path = __DIR__.'/database/migrations';
        foreach (\File::allFiles($path) as $file) {
            $tempArray[$path.'/'.\File::basename($file)] = app()->databasePath()."/migrations/".date('Y_m_d_His').'_'.\File::basename($file);
        }

        return $tempArray;
    }
}
