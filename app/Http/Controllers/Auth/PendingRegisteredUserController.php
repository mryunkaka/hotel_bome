<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

class PendingRegisteredUserController extends RegisteredUserController
{
    public function store(Request $request, CreatesNewUsers $creator): RegisterResponse
    {
        if (config('fortify.lowercase_usernames') && $request->has(Fortify::username())) {
            $request->merge([
                Fortify::username() => Str::lower($request->{Fortify::username()}),
            ]);
        }

        event(new Registered($creator->create($request->all())));

        return new class implements RegisterResponse
        {
            public function toResponse($request)
            {
                return $request->wantsJson()
                    ? response()->json('', 201)
                    : redirect()->route('login');
            }
        };
    }
}
