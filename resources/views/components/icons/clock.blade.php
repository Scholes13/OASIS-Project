@php
$iconClasses = isset($attributes) ? $attributes->merge(['class' => 'h-5 w-5'])->toHtml() : (isset($class) ? 'class="' . $class . '"' : 'class="h-5 w-5"');
@endphp
<svg {!! $iconClasses !!} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
</svg>
