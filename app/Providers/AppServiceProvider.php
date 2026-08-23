<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        Blade::directive('spaceless', function () {
            return '<?php ob_start(); ob_implicit_flush(false); ?>';
        });
        Blade::directive('endspaceless', function () {
            return "<?php echo trim(preg_replace('/>\s+</', '><', ob_get_clean())); ?>";
        });
    }
}
