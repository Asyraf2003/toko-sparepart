<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Application\Ports\Services\PdfRendererPort;
use Illuminate\Contracts\Container\Container;

final class DompdfPdfRenderer implements PdfRendererPort
{
    public function __construct(
        private readonly Container $app,
    ) {
    }

    public function renderBlade(
        string $view,
        array $data,
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): string {
        /** @var object $pdf */
        $pdf = $this->app->make('dompdf.wrapper');

        $pdf->loadView($view, $data);
        $pdf->setPaper($paper, $orientation);

        return (string) $pdf->output();
    }
}