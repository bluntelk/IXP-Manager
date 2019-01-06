<?php namespace IXP\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        'IXP\Listeners\Customer\Note\EmailOnChange',
    ];



    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'IXP\Events\Customer\BillingDetailsChanged' => [
            'IXP\Listeners\Customer\BillingDetailsChanged'
        ],

        'IXP\Events\Layer2Address\Added' => [
            'IXP\Listeners\Layer2Address\Changed',
        ],
        'IXP\Events\Layer2Address\Deleted' => [
            'IXP\Listeners\Layer2Address\Changed',
        ],

        'IXP\Events\User\Welcome' => [
            'IXP\Listeners\User\EmailWelcome'
        ],

        'IXP\Events\Auth\ForgotUsername' => [
            'IXP\Listeners\Auth\ForgotUsername'
        ],

        'IXP\Events\Auth\ForgotPassword' => [
            'IXP\Listeners\Auth\ForgotPassword'
        ],

        'IXP\Events\Auth\PasswordReset' => [
            'IXP\Listeners\Auth\PasswordReset'
        ],


    ];




    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }

}
