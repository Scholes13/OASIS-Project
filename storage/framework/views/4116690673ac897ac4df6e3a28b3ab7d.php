<?php
$iconClasses = isset($attributes) ? $attributes->merge(['class' => 'h-5 w-5'])->toHtml() : (isset($class) ? 'class="' . $class . '"' : 'class="h-5 w-5"');
?>
<svg <?php echo $iconClasses; ?> fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
</svg><?php /**PATH E:\Learning\WGProject\Numbering\resources\views/components/icons/check-circle.blade.php ENDPATH**/ ?>