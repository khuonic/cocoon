<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Budget/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Budget/Create');
    }
}
