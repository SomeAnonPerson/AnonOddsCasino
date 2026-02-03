<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global Middleware
        $middleware->use([
            \VanguardLTE\Http\Middleware\VerifyInstallation::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \VanguardLTE\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \VanguardLTE\Http\Middleware\TrustProxies::class,
        ]);

        // Web Middleware Group
        $middleware->web(append: [
            \VanguardLTE\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \VanguardLTE\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \VanguardLTE\Http\Middleware\SelectLanguage::class,
        ]);

        // API Middleware Group
        $middleware->api(prepend: [
            \VanguardLTE\Http\Middleware\UseApiGuard::class,
        ]);

        // API Rate Limiting
        $middleware->throttleApi('60,1');

        // Route Middleware Aliases
        $middleware->alias([
            'auth' => \VanguardLTE\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'guest' => \VanguardLTE\Http\Middleware\RedirectIfAuthenticated::class,
            'registration' => \VanguardLTE\Http\Middleware\Registration::class,
            'session.database' => \VanguardLTE\Http\Middleware\DatabaseSession::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'role' => \jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyRole::class,
            'permission' => \jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyPermission::class,
            'level' => \jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyLevel::class,
            'ipcheck' => \VanguardLTE\Http\Middleware\IpMiddleware::class,
            'siteisclosed' => \VanguardLTE\Http\Middleware\SiteIsClosed::class,
            'localization' => \VanguardLTE\Http\Middleware\SelectLanguage::class,
            'shopzero' => \VanguardLTE\Http\Middleware\ShopZero::class,
            'shop_not_zero' => \VanguardLTE\Http\Middleware\ShopNotZero::class,
            'only_for_admin' => \VanguardLTE\Http\Middleware\OnlyForAdmin::class,
            'permission_api' => \VanguardLTE\Http\Middleware\VerifyPermission::class,
            'checker' => \VanguardLTE\Http\Middleware\Checker::class,
            '2fa' => \PragmaRX\Google2FALaravel\Middleware::class,
        ]);
    })
    ->withSchedule(function ($schedule) {
        // Queue Worker
        $schedule->command('queue:work --daemon')
            ->everyMinute()
            ->withoutOverlapping();

        // Daily Database Backup
        $schedule->call(function () {
            \Spatie\DbDumper\Databases\MySql::create()
                ->setDbName(config('database.connections.mysql.database'))
                ->setUserName(config('database.connections.mysql.username'))
                ->setPassword(config('database.connections.mysql.password'))
                ->dumpToFile(base_path() . '/backups/' . date('Hi_dmY') . '.sql');
        })->daily();

        // Scheduled Tasks
        $timeout = 45;

        $schedule->call(new \VanguardLTE\Console\Schedules\Tournaments($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\SMSBonuses($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\Securities($timeout))->everyFiveMinutes();
        $schedule->call(new \VanguardLTE\Console\Schedules\ShopCreates($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\ShopDeletes($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\Synchronization($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\QuickShops($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\HierarchyUsersCache($timeout))->everyFiveMinutes();
        $schedule->call(new \VanguardLTE\Console\Schedules\TreeCache($timeout))->everyFiveMinutes();
        $schedule->call(new \VanguardLTE\Console\Schedules\HotGamesCache($timeout))->everyThreeHours();
        $schedule->call(new \VanguardLTE\Console\Schedules\BankDecrease($timeout))->everyThreeHours();
        $schedule->call(new \VanguardLTE\Console\Schedules\Notifications($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\ClearLogs($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\GameEvents($timeout))->everyFiveMinutes();
        $schedule->call(new \VanguardLTE\Console\Schedules\RemoveGamesWithoutFolder($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\SMSMailings($timeout))->everyMinute();
        $schedule->call(new \VanguardLTE\Console\Schedules\EveryFiveMinutesCleanUp($timeout))->everyFiveMinutes();
        $schedule->call(new \VanguardLTE\Console\Schedules\EveryMinuteCleanUp($timeout))->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
