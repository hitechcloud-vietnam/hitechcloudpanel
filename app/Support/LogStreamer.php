<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class LogStreamer
{
    /**
     * @param  callable(): array{chunk:string, reset?:bool, completed?:bool}  $resolver
     */
    public static function make(callable $resolver, int $maxIterations = 300, int $sleepMicroseconds = 1000000): StreamedResponse
    {
        return response()->stream(function () use ($resolver, $maxIterations, $sleepMicroseconds): void {
            ignore_user_abort(true);
            @set_time_limit(0);

            echo "retry: 1000\n\n";

            $sentInitialPayload = false;

            for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
                if (connection_aborted()) {
                    break;
                }

                $payload = $resolver();
                $chunk = (string) ($payload['chunk'] ?? '');
                $reset = (bool) ($payload['reset'] ?? false);
                $completed = (bool) ($payload['completed'] ?? false);

                if ($chunk !== '' || ! $sentInitialPayload || $reset) {
                    self::send('log', [
                        'chunk' => $chunk,
                        'reset' => $reset,
                        'completed' => $completed,
                    ]);

                    $sentInitialPayload = true;
                } else {
                    self::send('ping', [
                        'completed' => $completed,
                    ]);
                }

                if ($completed) {
                    break;
                }

                usleep($sleepMicroseconds);
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Type' => 'text/event-stream',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function send(string $event, array $payload): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }
}