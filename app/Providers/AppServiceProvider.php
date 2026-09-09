<?php

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config): BrevoApiTransport {
            return new BrevoApiTransport(
                app(HttpClient::class),
                (string) ($config['api_key'] ?? ''),
                (string) ($config['endpoint'] ?? 'https://api.brevo.com/v3/smtp/email'),
                (int) ($config['timeout'] ?? 15),
            );
        });
    }
}
