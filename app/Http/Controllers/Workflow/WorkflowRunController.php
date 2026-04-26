<?php

namespace App\Http\Controllers\Workflow;

use App\Actions\Workflow\RunWorkflow;
use App\Enums\WorkflowRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowResource;
use App\Http\Resources\WorkflowRunResource;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Support\LogStreamer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Prefix('workflows/{workflow}/runs')]
#[Middleware(['auth', 'has-project'])]
class WorkflowRunController extends Controller
{
    #[Get('/', name: 'workflow-runs')]
    public function index(Workflow $workflow): Response
    {
        $this->authorize('view', $workflow);

        return inertia('workflow-runs/index', [
            'workflow' => WorkflowResource::make($workflow),
            'workflowRuns' => WorkflowRunResource::collection(
                $workflow
                    ->runs()
                    ->orderByDesc('created_at')
                    ->simplePaginate(config('web.pagination_size'))
            ),
        ]);
    }

    #[Post('/', name: 'workflow-runs.store')]
    public function store(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);

        $run = app(RunWorkflow::class)->run(user(), $workflow, $request->all());

        return redirect()
            ->route('workflow-runs.show', ['workflow' => $workflow->id, 'workflowRun' => $run->id])
            ->with('success', 'Workflow started successfully');
    }

    #[Get('/{workflowRun}', name: 'workflow-runs.show')]
    public function show(Workflow $workflow, WorkflowRun $workflowRun): Response
    {
        $this->authorize('view', $workflow);

        return inertia('workflow-runs/show', [
            'workflow' => WorkflowResource::make($workflow),
            'workflowRun' => WorkflowRunResource::make($workflowRun),
        ]);
    }

    #[Get('/{workflowRun}/log', name: 'workflow-runs.log')]
    public function log(Workflow $workflow, WorkflowRun $workflowRun): string
    {
        $this->authorize('view', $workflow);

        return $workflowRun->getLogContent();
    }

    #[Get('/{workflowRun}/log/stream', name: 'workflow-runs.stream')]
    public function stream(Workflow $workflow, WorkflowRun $workflowRun): StreamedResponse
    {
        $this->authorize('view', $workflow);

        $offset = 0;

        return LogStreamer::make(function () use ($workflowRun, &$offset): array {
            $payload = $workflowRun->getLogStreamChunk($offset);
            $offset = $payload['next_offset'];

            return [
                'chunk' => $payload['chunk'],
                'reset' => $payload['reset'],
                'completed' => $workflowRun->fresh()?->status !== WorkflowRunStatus::RUNNING,
            ];
        });
    }
}
