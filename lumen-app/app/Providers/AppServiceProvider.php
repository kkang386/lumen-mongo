<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Validator;
class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Validator::extend('name_string', function($attribute, $value, $parameters) {
            if(preg_match('/^[A-Z][ a-zA-Z]+$/', $value)){
                return true;
            }
            return false;
        });

        Validator::extend('datetime_string', function($attribute, $value, $parameters) {
            try {
                $UTC = new \DateTimeZone("UTC");
                new \DateTime($value, $UTC);
            } catch (\Exception $e) {
                return false;
            }
            return true;
        });

        Validator::extend('timezone_string', function($attribute, $value, $parameters) {
            try {
                new \DateTimeZone($value);
            } catch (\Exception $e) {
                return false;
            }
            return true;
        });

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
