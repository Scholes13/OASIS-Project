<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modules\WNS\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class TestPDFGeneration extends Command
{
    protected $signature = 'test:pdf-generation {pr_id?}';
    protected $description = 'Test PDF generation for purchase requests';

    public function handle()
    {
        $prId = $this->argument('pr_id');
        
        if (!$prId) {
            // Get the latest PR
            $purchaseRequest = PurchaseRequest::with([
                'user',
                'department', 
                'businessUnit',
                'items',
                'approvals.approver'
            ])->latest()->first();
            
            if (!$purchaseRequest) {
                $this->error('No purchase requests found in database');
                return 1;
            }
        } else {
            $purchaseRequest = PurchaseRequest::with([
                'user',
                'department',
                'businessUnit', 
                'items',
                'approvals.approver'
            ])->find($prId);
            
            if (!$purchaseRequest) {
                $this->error("Purchase request with ID {$prId} not found");
                return 1;
            }
        }
        
        $this->info("Testing PDF generation for PR: {$purchaseRequest->pr_number}");
        
        try {
            // Test view rendering first
            $this->info('Testing view rendering...');
            try {
                $viewContent = view('purchase-requests.pdf-simple', compact('purchaseRequest'))->render();
                $this->info('✓ View rendered successfully');
                $this->info("Content length: " . strlen($viewContent) . " characters");
                
                if (strlen($viewContent) === 0) {
                    $this->error('View content is empty! Checking for errors...');
                    
                    // Try to render with error reporting
                    ob_start();
                    try {
                        echo view('purchase-requests.pdf-simple', compact('purchaseRequest'));
                    } catch (\Exception $viewError) {
                        $this->error('View rendering error: ' . $viewError->getMessage());
                        throw $viewError;
                    }
                    $output = ob_get_clean();
                    $this->info("Raw output length: " . strlen($output));
                }
            } catch (\Exception $viewError) {
                $this->error('View rendering failed: ' . $viewError->getMessage());
                throw $viewError;
            }
            
            // Test PDF generation
            $this->info('Testing PDF generation...');
            $pdf = Pdf::loadView('purchase-requests.pdf-simple', compact('purchaseRequest'));
            $pdf->setPaper('A4', 'landscape');
            
            // Generate PDF content
            $pdfContent = $pdf->output();
            $this->info('✓ PDF generated successfully');
            $this->info("PDF size: " . strlen($pdfContent) . " bytes");
            
            // Save test PDF
            $filename = storage_path('app/test-pr-' . $purchaseRequest->id . '.pdf');
            file_put_contents($filename, $pdfContent);
            $this->info("✓ Test PDF saved to: {$filename}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('PDF generation failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}