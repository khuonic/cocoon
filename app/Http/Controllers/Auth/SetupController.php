<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetupRequest;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect('/login');
        }

        return Inertia::render('auth/Setup');
    }

    public function store(SetupRequest $request): RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect('/login');
        }

        $validated = $request->validated();
        $allowedUsers = config('cocon.allowed_users', []);
        $hashedPassword = Hash::make($validated['password']);

        $currentUser = null;

        foreach ($allowedUsers as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $hashedPassword,
                'email_verified_at' => now(),
            ]);

            if ($userData['email'] === $validated['email']) {
                $currentUser = $user;
            }
        }

        if (ExpenseCategory::query()->doesntExist()) {
            app()->make(\Illuminate\Contracts\Console\Kernel::class);
            (new ExpenseCategorySeeder)->run();
        }

        Auth::login($currentUser);

        return redirect('/');
    }
}
