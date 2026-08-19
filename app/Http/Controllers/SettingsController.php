<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

/**
 * Renders user account settings screens (Inertia).
 */
class SettingsController extends Controller
{
    /**
     * Security settings — two-factor authentication management.
     */
    public function security(): \Inertia\Response
    {
        return Inertia::render('Settings/Security');
    }
}
