<?php 

namespace Asanak\Sms;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/asanak.php', 'asanak');

        $this->app->singleton(SmsClient::class, function ($app) {
            $config = $app['config']['asanak'];

            return new SmsClient(
                $config['username'],
                $config['password'],
                $config['base_url'],
                $config['logging']
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/asanak.php' => config_path('asanak.php'),
        ], 'asanak-config');
    }
}
