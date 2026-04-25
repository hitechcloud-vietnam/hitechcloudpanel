<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeploymentScriptIsEmptyException extends Exception
{
    public function render(Request $request): RedirectResponse
    {
        if ($request->header('X-Inertia')) {
            return back()->with('error', 'Cannot deploy an empty deployment script.');
        }

        throw ValidationException::withMessages([
            'deployment_script' => 'Deployment script cannot be empty.',
        ]);
    }
}
