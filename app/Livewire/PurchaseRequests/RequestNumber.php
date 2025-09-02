<?php

namespace App\Livewire\PurchaseRequests;

use App\Services\Modules\WNS\PRNumberingService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RequestNumber extends Component
{
    // Form fields for number request (simplified)
    public $purpose = '';     // Keperluan
    public $description = ''; // Deskripsi
    
    // Auto-generated fields (display only)
    public $submission_date;
    public $department_name;
    public $department_code;
    public $user_name;
    
    // State
    public $isLoading = false;
    public $generatedNumber = null;
    public $numberDetails = null;
    
    // Validation rules (simplified)
    protected $rules = [
        'purpose' => 'required|string|min:3|max:500',
        'description' => 'required|string|min:10|max:1000',
    ];

    protected $messages = [
        'purpose.required' => 'Keperluan harus diisi.',
        'purpose.min' => 'Keperluan minimal 3 karakter.',
        'description.required' => 'Deskripsi harus diisi.',
        'description.min' => 'Deskripsi minimal 10 karakter.',
    ];

    public function mount()
    {
        // Auto-populate data from current user and session
        $this->submission_date = Carbon::today()->format('d/m/Y');
        $this->user_name = Auth::user()->name;
        
        // Get department from user's current department
        $user = Auth::user();
        if ($user->primaryDepartment) {
            $this->department_name = $user->primaryDepartment->name;
            $this->department_code = $user->primaryDepartment->code;
        } else {
            $this->department_name = 'Department not set';
            $this->department_code = 'N/A';
        }
    }

    public function submitRequest()
    {
        \Illuminate\Support\Facades\Log::info('=== submitRequest START ===', [
            'purpose' => $this->purpose,
            'description' => $this->description,
            'purpose_length' => strlen($this->purpose ?? ''),
            'description_length' => strlen($this->description ?? ''),
            'user_id' => Auth::id(),
            'session_business_unit' => session('current_business_unit_id'),
        ]);
        
        $this->isLoading = true;
        
        try {
            \Illuminate\Support\Facades\Log::info('=== Validation START ===');
            
            // Trim whitespace before validation
            $this->purpose = trim($this->purpose ?? '');
            $this->description = trim($this->description ?? '');
            
            \Illuminate\Support\Facades\Log::info('After trim', [
                'purpose' => $this->purpose,
                'description' => $this->description,
            ]);
            
            // Validate the form
            $this->validate();
            
            \Illuminate\Support\Facades\Log::info('=== Validation PASSED ===');
            
            // Double check that fields are not empty after trimming
            if (empty($this->purpose) || empty($this->description)) {
                throw new \Exception('Keperluan dan Deskripsi harus diisi.');
            }
            
            \Illuminate\Support\Facades\Log::info('=== Getting PRNumberingService ===');
            $numberingService = app(PRNumberingService::class);
            
            \Illuminate\Support\Facades\Log::info('=== Calling generatePRNumber ===');
            // Generate PR number atomically when user submits
            $result = $numberingService->generatePRNumber(
                Auth::user(),
                Carbon::today()
            );
            
            \Illuminate\Support\Facades\Log::info('=== PR Number Generated ===', $result);
            
            $this->generatedNumber = $result['formatted_number'];
            $this->numberDetails = [
                'formatted_number' => $result['formatted_number'],
                'sequence_id' => $result['sequence_id'],
                'department_code' => $result['department_code'],
                'year' => Carbon::today()->year,
                'month' => Carbon::today()->month,
                'sequence_number' => $result['sequence_number'],
                'submission_date' => Carbon::today()->format('Y-m-d'),
                'purpose' => $this->purpose,
                'description' => $this->description,
                'currency' => 'IDR', // Default currency
                'requested_by' => Auth::user()->name,
                'requested_at' => now()->format('Y-m-d H:i:s'),
            ];
            
            \Illuminate\Support\Facades\Log::info('=== Setting success message ===');
            session()->flash('success', "Nomor PR {$this->generatedNumber} berhasil di-generate.");
            
            \Illuminate\Support\Facades\Log::info('=== submitRequest SUCCESS ===');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('=== Validation Error ===', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            // Re-throw validation exceptions to show field errors
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('=== PR Number Generation Error ===', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Gagal generate nomor PR: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
            \Illuminate\Support\Facades\Log::info('=== submitRequest END ===');
        }
    }

    public function createPRForm()
    {
        try {
            if (!$this->generatedNumber || !$this->numberDetails) {
                session()->flash('error', 'Please generate a PR number first.');
                return;
            }
            
            // Store the number details in session for the next step
            session([
                'pr_number_details' => $this->numberDetails
            ]);
            
            // Use regular redirect without navigate
            return $this->redirect(route('purchase-requests.create-with-number'));
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error redirecting to form: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('CreatePRForm Error: ' . $e->getMessage());
        }
    }

    public function getNextNumberPreview()
    {
        // Only show general format, not actual next number to avoid race conditions
        $user = Auth::user();
        if ($user->primaryDepartment) {
            $deptCode = $user->primaryDepartment->code;
            $year = Carbon::today()->year;
            $month = Carbon::today()->month;
            return sprintf("PR.%s/%d/%02d/XXX", $deptCode, $year, $month);
        }
        return 'PR.DEPT/YYYY/MM/XXX';
    }

    public function render()
    {
        return view('livewire.purchase-requests.request-number');
    }

    public function updatedPurpose($value)
    {
        \Illuminate\Support\Facades\Log::info('Purpose updated: ' . $value);
        $this->resetErrorBag('purpose');
    }

    public function updatedDescription($value)
    {
        \Illuminate\Support\Facades\Log::info('Description updated: ' . $value);
        $this->resetErrorBag('description');
    }

    /**
     * Check if form is valid for submission
     */
    public function getIsFormValidProperty()
    {
        $purpose = trim($this->purpose ?? '');
        $description = trim($this->description ?? '');
        
        return strlen($purpose) >= 3 && strlen($description) >= 10;
    }
}