<?php

namespace App\Http\Controllers;

use App\Http\Requests\SweetMessage\StoreSweetMessageRequest;
use App\Models\SweetMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class SweetMessageController extends Controller
{
    public function store(StoreSweetMessageRequest $request): RedirectResponse
    {
        SweetMessage::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'content' => $request->validated('content'),
                'uuid' => Str::uuid(),
            ],
        );

        return to_route('dashboard');
    }
}
