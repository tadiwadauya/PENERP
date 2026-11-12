<?php

namespace App\Http\Middleware;

use App\Models\PensionsAdministration\Updates\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberPortalAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('member_portal.member_id');
        if (!$id || !Member::whereKey($id)->exists()) {
            $request->session()->forget('member_portal');
            return redirect()->route('member-portal.login');
        }
        return $next($request);
    }
}
