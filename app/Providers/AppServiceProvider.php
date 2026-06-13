<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Payments\SquareGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, SquareGateway::class);
    }

    public function boot(): void
    {
        // Super admins and admins both pass — the only difference is that
        // super admins are excluded from booking-notification emails (see
        // User::adminEmails).
        Gate::define('admin', fn (User $user) => in_array($user->role, UserRole::staff(), true));

        // The admin layout hosts the "Create booking" modal on every page,
        // so it always needs the service list for the dropdown.
        View::composer('layouts.admin', function ($view) {
            $view->with('bookableServices', Service::orderBy('display_order')->get());
        });
    }
}
