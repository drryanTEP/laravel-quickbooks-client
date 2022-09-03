<?php

namespace Spinen\QuickBooks\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Spinen\QuickBooks\Client;
use App\Models\Form;
use App\Models\School;

/**
 * Class ClientServiceProvider
 *
 * @package Spinen\QuickBooks
 */
class ClientServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Client::class,
        ];
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function (Application $app) {
            if( request()->serverMemo['dataMeta']['models']['form']['id'] ?? false ) {
                $school = Form::find( request()->serverMemo['dataMeta']['models']['form']['id'] )->school;

                $token = ( $school->quickBooksToken)
                ? :  $school->quickBooksToken()
                              ->make();
            } else {
                
                 if( request()->route('school') ) {
                     $school = School::find( request()->route('school') );
                } else {
                    $school = $app->auth->user->school();
                }

                $token = ($school->quickBooksToken)
                ? : $school->quickBooksToken()
                              ->make();
            }

            return new Client($app->config->get('quickbooks'), $token);
        });

        $this->app->alias(Client::class, 'QuickBooks');
    }
}
