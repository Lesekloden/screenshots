<?php

declare(strict_types=1);

namespace Lesekloden\Screenshots;

class Screenshots
{
    public function __construct(private readonly ScreenshotsCapture $capture) {}

    /**
     * @return array<string, list<array{name: string, path: string, width: int, height: int}>>
     */
    public function captureAll(): array
    {
        return $this->capture->captureAll();
    }
}
