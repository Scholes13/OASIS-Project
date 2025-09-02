<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        
        // Automatically set primary business unit context
        $this->setBusinessUnitContext();
    }
    
    /**
     * Automatically set business unit context for seamless login
     */
    protected function setBusinessUnitContext(): void
    {
        $user = Auth::user();
        
        // Super admins can bypass business unit requirement
        if ($user->global_role === 'super_admin') {
            // Set default session for super admin access
            session([
                'current_business_unit_id' => null,
                'current_business_unit_code' => 'WG',
                'current_business_unit_name' => 'Werkudara Group',
                'current_user_role' => 'super_admin',
                'current_department_id' => null,
            ]);
            return;
        }
        
        // Get primary business unit (first assigned) or fall back to first active
        $primaryBusinessUnit = $user->businessUnits()
            ->with('businessUnit')
            ->where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->first();
            
        if ($primaryBusinessUnit) {
            $businessUnit = $primaryBusinessUnit->businessUnit;
            
            // Set session context for seamless experience
            session([
                'current_business_unit_id' => $businessUnit->id,
                'current_business_unit_code' => $businessUnit->code,
                'current_business_unit_name' => $businessUnit->name,
                'current_user_role' => $primaryBusinessUnit->role,
                'current_department_id' => $primaryBusinessUnit->department_id,
            ]);
        } else {
            // User has no business unit assigned - logout and show error
            Auth::logout();
            
            throw ValidationException::withMessages([
                'form.email' => 'No business unit assigned. Please contact administrator.',
            ]);
        }
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
