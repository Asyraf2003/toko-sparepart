<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

interface PdfRendererPort
{
    /**
     * Render Blade view menjadi bytes PDF.
     *
     * @param array<string,mixed> $data
     */
    public function renderBlade(
        string $view,
        array $data,
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): string;
}