<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Invitation;
use App\User;

class RegisterController extends Controller
{


    public function register()
    {
        $invitation = Invitation::findByCode(request('invitation_code'));
        $user = User::create([
            'email' => request('email'),
            'password' => bcrypt(request('password')),
        ]);

        $invitation->update([
            'user_id' => $user->id
        ]);

        auth()->login($user);

        return redirect()->route('backstage.concerts.index');
    }
}
