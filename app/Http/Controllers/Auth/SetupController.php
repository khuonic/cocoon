<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetupRequest;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\JokeSeeder;
use Database\Seeders\ShoppingListSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
            app()->make(Kernel::class);
            (new ExpenseCategorySeeder)->run();
            (new JokeSeeder)->run();
            (new ShoppingListSeeder)->run();
        }

        Auth::login($currentUser);

        $this->flashSyncToken($validated['email'], $validated['password']);

        return redirect('/');
    }

    private function flashSyncToken(string $email, string $password): void
    {
        $syncApiUrl = config('cocon.sync_api_url');
        if (! $syncApiUrl) {
            return;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->post("{$syncApiUrl}/api/login", [
                'email' => $email,
                'password' => $password,
                'device_name' => 'cocoon-mobile',
            ]);

            if ($response->ok() && $response->json('token')) {
                session()->flash('sync_token', $response->json('token'));
            }
        } catch (\Exception $e) {
            // Sync token non critique au setup — l'app se synchronisera au prochain lancement
        }
    }
}
