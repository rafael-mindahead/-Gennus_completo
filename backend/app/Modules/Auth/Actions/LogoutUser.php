<?php

namespace App\Modules\Auth\Actions;

use Illuminate\Support\Facades\Auth;

final class LogoutUser
{
    public function execute(): void
    {
        Auth::Logout();
    }
}