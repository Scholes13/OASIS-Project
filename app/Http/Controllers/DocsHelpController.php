<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DocsHelpController extends Controller
{
    /**
     * Display the docs & help page.
     */
    public function index(): Response
    {
        return Inertia::render('DocsHelp/Index');
    }
}
