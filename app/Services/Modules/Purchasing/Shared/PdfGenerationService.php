<?php

namespace App\Services\Modules\Purchasing\Shared;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

/**
 * Browsershot-backed PDF renderer used by both Purchase Request and
 * Stock Request download endpoints.
 *
 * Replaces the duplicated `downloadPdfPublic` Browsershot blocks that
 * previously lived inside `PurchaseRequestController` and
 * `StockRequestController`.  Behaviour parity is preserved exactly:
 *  - same Browsershot chain (format, landscape, margins, delay,
 *    noSandbox, disableWebSecurity)
 *  - same remote-instance fallback wired from `pdf.browsershot.remote_url`
 *  - same response shape (raw `Response` with PDF bytes + download
 *    headers; no temp file, no `BinaryFileResponse`)
 *  - same failure handler: log the error and redirect to the caller's
 *    fallback URL with a flash error string
 *
 * Caller is responsible for loading model relationships, computing
 * QR codes, and resolving the view name + filename — this service
 * intentionally only knows how to turn a Blade/Inertia view into a
 * PDF response.
 */
class PdfGenerationService
{
    /**
     * Render a Blade/Inertia view to a downloadable PDF.
     *
     * Recognised options:
     *  - `filename` (string, required): output filename including `.pdf`
     *  - `fallback_url` (string, required): URL to redirect to when
     *    Browsershot raises an exception
     *  - `fallback_message` (string, optional): flash error message on
     *    fallback redirect
     *  - `timeout` (int, optional): Browsershot timeout in seconds;
     *    defaults to 120 to match historical behaviour
     *  - `set_time_limit` (int, optional): PHP `set_time_limit` value
     *    applied before generation; defaults to
     *    `features.purchasing.pdf_generation_timeout` (300)
     *  - `paper` (string, optional): page format, defaults to `A4`
     *  - `orientation` (`portrait`|`landscape`, optional): defaults to
     *    `landscape` to match historical PR/ST behaviour
     *  - `delay` (int, optional): render delay in ms; defaults to 2000
     *  - `margins` (array<int>, optional): `[top,right,bottom,left]`
     *    in mm; defaults to `[10,10,10,10]`
     *
     * @param  array<string, mixed>  $data  View data
     * @param  array<string, mixed>  $options  Generation options (see above)
     */
    public function streamPdf(string $viewName, array $data, array $options = []): Response|RedirectResponse
    {
        $filename = $options['filename'] ?? 'document.pdf';
        $fallbackUrl = $options['fallback_url'] ?? null;
        $fallbackMessage = $options['fallback_message']
            ?? 'Automatic PDF generation failed. Please use Ctrl+P to save as PDF.';

        // Increase PHP execution time before we hand off to Browsershot.
        // Mirrors the legacy controllers, which set this immediately on entry.
        $phpTimeLimit = (int) ($options['set_time_limit']
            ?? config('features.purchasing.pdf_generation_timeout', 300));
        if ($phpTimeLimit > 0) {
            set_time_limit($phpTimeLimit);
        }

        try {
            // Render the view to a fully-resolved HTML string so that
            // Browsershot does not need to fetch a URL (avoids URL
            // timeouts on hosting that cannot loopback to itself).
            $html = view($viewName, $data)->render();

            $browsershot = $this->buildBrowsershot($html, $options);
            $pdfContent = $browsershot->pdf();

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        } catch (\Exception $e) {
            Log::error('Browsershot PDF generation failed: '.$e->getMessage());

            if ($fallbackUrl) {
                return redirect($fallbackUrl)->with('error', $fallbackMessage);
            }

            return back()->with('error', $fallbackMessage);
        }
    }

    /**
     * Build the Browsershot instance with the legacy chain plus the
     * optional remote-instance wiring.  Kept private so callers cannot
     * accidentally diverge from the canonical configuration.
     *
     * @param  array<string, mixed>  $options
     */
    private function buildBrowsershot(string $html, array $options): Browsershot
    {
        $paper = $options['paper'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'landscape';
        $timeout = (int) ($options['timeout'] ?? 120);
        $delay = (int) ($options['delay'] ?? 2000);
        $margins = $options['margins'] ?? [10, 10, 10, 10];

        $browsershot = Browsershot::html($html)
            ->format($paper)
            ->margins($margins[0], $margins[1], $margins[2], $margins[3])
            ->timeout($timeout)
            ->noSandbox()
            ->disableWebSecurity()
            ->setDelay($delay);

        if ($orientation === 'landscape') {
            $browsershot->landscape();
        }

        if ($remoteUrl = config('pdf.browsershot.remote_url')) {
            $parsed = parse_url($remoteUrl);
            $browsershot->setRemoteInstance(
                $parsed['host'] ?? '127.0.0.1',
                (int) ($parsed['port'] ?? 9222),
            );
        }

        return $browsershot;
    }
}
