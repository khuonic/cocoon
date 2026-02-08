<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ShoppingListController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Shopping/Index');
    }
}
