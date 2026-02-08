<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Bookmarks/Index');
    }
}
