@php
$iconClasses = isset($attributes) ? $attributes->merge(['class' => 'h-5 w-5'])->toHtml() : (isset($class) ? 'class="' . $class . '"' : 'class="h-5 w-5"');
@endphp
<svg {!! $iconClasses !!} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
</svg>
