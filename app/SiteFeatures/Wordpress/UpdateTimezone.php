<?php

namespace App\SiteFeatures\Wordpress;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Exceptions\SSHError;
use App\SiteFeatures\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UpdateTimezone extends Action
{
    public function name(): string
    {
        return 'Update Timezone';
    }

    public function active(): bool
    {
        return true;
    }

    public function form(): DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('timezone')
                ->text()
                ->label('Timezone')
                ->default(data_get($this->site->type_data, 'wordpress_timezone', config('app.timezone', 'UTC')))
                ->placeholder('Asia/Ho_Chi_Minh')
                ->description('Use a valid PHP timezone identifier.'),
        ]);
    }

    /**
     * @throws SSHError
     */
    public function handle(Request $request): void
    {
        $validated = Validator::make($request->all(), [
            'timezone' => ['required', 'string', 'timezone:all'],
        ])->validate();

        $timezone = $validated['timezone'];

        $this->site->server->ssh($this->site->user)->exec(
            view('ssh.site-features.wordpress.update-timezone', [
                'path' => $this->site->path,
                'timezone' => $timezone,
            ]),
            'wordpress-update-timezone',
            $this->site->id
        );

        $typeData = $this->site->type_data ?? [];
        $typeData['wordpress_timezone'] = $timezone;
        $this->site->type_data = $typeData;
        $this->site->save();

        $request->session()->flash('success', 'WordPress timezone updated successfully.');
    }
}
