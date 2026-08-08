<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the currently authenticated user's profile.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        $user->load([
            'organisationUnit',
            'jobTitle',
            'grade',
            'supervisor',
            'roles',
            'dashboards',
        ]);

        return view(
            'user-management.profile.show',
            compact('user')
        );
    }
}