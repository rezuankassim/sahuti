<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Display the privacy policy page
     */
    public function show(): Response
    {
        return Inertia::render('privacy-policy');
    }
}
