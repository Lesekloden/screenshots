<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots\Console\Commands;

use Illuminate\Console\Command;
use Lesekloden\Screenshots\ScreenshotsCapture;
use Throwable;

final class ScreenshotsCaptureCommand extends Command
{
    protected $signature = 'make:screenshots';

    protected $description = 'Capture all configured page sections as PNG screenshots.';

    public function handle(ScreenshotsCapture $screenshots): int
    {
        try {
            $pages = $screenshots->captureAll();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $total = 0;

        foreach ($pages as $directory => $results) {
            $this->line("<info>{$directory}</info>");

            foreach ($results as $result) {
                $this->line(
                    sprintf(
                        '  Captured %s (%dx%d): %s',
                        $result['name'],
                        $result['width'],
                        $result['height'],
                        $result['path'],
                    ),
                );
            }

            $total += count($results);
        }

        $this->info(
            sprintf(
                'Captured %d screenshot%s across %d page%s.',
                $total,
                $total === 1 ? '' : 's',
                count($pages),
                count($pages) === 1 ? '' : 's',
            ),
        );

        return self::SUCCESS;
    }
}
