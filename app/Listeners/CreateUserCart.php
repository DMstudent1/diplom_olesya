<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateUserCart
{
    public function handle(UserRegistered $event)
    {
        Cart::create([
            'user_id' => $event->user->id,
        ]);
    }
}
