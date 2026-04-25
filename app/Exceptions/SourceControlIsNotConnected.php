<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SourceControlIsNotConnected extends Exception
{
    public function render(Request $request): ?RedirectResponse
    {
        if ($request->header('X-Inertia')) {
            return back()->with('error', 'Source control is not connected.');
        }

        return null;
    }
}
