<?php

namespace App\Http\Controllers;

use App\Models\Realestate\Realestate;
use Illuminate\Http\Response;

class RealestateController extends Controller
{
    public function show(string $url): Response
    {
        $realestate = Realestate::query()
            ->where('slug', $url)
            ->with(['country', 'region', 'city', 'district'])
            ->firstOrFail();

        // Drafts are available for review in the admin session, but not publicly.
        abort_unless($realestate->active || auth()->check(), 404);

        return response()->view('realestate.show', compact('realestate'))
            ->header('X-Robots-Tag', $realestate->active ? 'index, follow' : 'noindex, nofollow')
            ->header('Cache-Control', $realestate->active ? 'public, max-age=300' : 'private, no-store');
    }
}
