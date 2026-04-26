<?php

namespace App\ServerFeatures\System;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\ServerFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UpdateHostname extends Action
{
    public function name(): string
    {
        return 'Update Hostname';
    }

    public function active(): bool
    {
        return true;
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('hostname')
                ->text()
                ->label('Hostname')
                ->default($this->server->hostname())
                ->placeholder('example-server')
                ->description('Lowercase letters, numbers, and hyphens only.'),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $validated = Validator::make($request->all(), [
            'hostname' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/'],
        ])->validate();

        $hostname = strtolower($validated['hostname']);

        $this->server->ssh()->exec(
            view('ssh.server-features.system.update-hostname', [
                'hostname' => $hostname,
            ]),
            'update-hostname'
        );

        $request->session()->flash('success', 'Hostname updated successfully.');
    }
}
