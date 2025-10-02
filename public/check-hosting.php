<?php
/**
 * Hosting Environment Diagnostic Script
 * Upload to: public/check-hosting.php
 * Access via: https://devlopment.werkudara.com/check-hosting.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hosting Environment Check</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #1e40af; margin-top: 30px; border-left: 4px solid #3b82f6; padding-left: 10px; }
        .check { padding: 10px; margin: 5px 0; background: #f9fafb; border-left: 4px solid #ddd; }
        .check.success { border-left-color: #10b981; background: #ecfdf5; }
        .check.error { border-left-color: #ef4444; background: #fef2f2; }
        .check.warning { border-left-color: #f59e0b; background: #fffbeb; }
        .icon { font-weight: bold; margin-right: 8px; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .status-ok { color: #10b981; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Hosting Environment Diagnostic</h1>
        <p><strong>Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>

        <?php
        // === LARAVEL DETECTION ===
        echo "<h2>1. Laravel Installation</h2>";
        
        $indexExists = file_exists(__DIR__.'/index.php');
        $htaccessExists = file_exists(__DIR__.'/.htaccess');
        
        echo check("index.php exists", $indexExists);
        echo check(".htaccess exists", $htaccessExists);
        
        if ($htaccessExists) {
            $htaccess = file_get_contents(__DIR__.'/.htaccess');
            $hasRewrite = strpos($htaccess, 'RewriteEngine On') !== false;
            echo check(".htaccess has RewriteEngine", $hasRewrite);
        }
        
        // === STORAGE SYMLINK ===
        echo "<h2>2. Storage Symlink</h2>";
        
        $storagePath = __DIR__.'/storage';
        $symlinkExists = file_exists($storagePath);
        $isSymlink = is_link($storagePath);
        
        echo check("public/storage exists", $symlinkExists);
        echo check("public/storage is symlink", $isSymlink);
        
        if ($isSymlink) {
            $target = readlink($storagePath);
            echo check("Symlink target: $target", true);
            
            $targetExists = file_exists($target);
            echo check("Target directory exists", $targetExists);
        } else {
            echo check("❌ CRITICAL: Storage symlink NOT created!", false);
            echo "<div class='check error'><strong>Fix:</strong> Run <code>php artisan storage:link</code></div>";
        }
        
        // === VENDOR DIRECTORY ===
        echo "<h2>3. Vendor/Livewire Files</h2>";
        
        $vendorLivewire = __DIR__.'/vendor/livewire';
        $vendorLivewireExists = file_exists($vendorLivewire);
        
        echo check("public/vendor/livewire/ exists", $vendorLivewireExists, 'warning');
        
        if ($vendorLivewireExists) {
            $files = glob($vendorLivewire.'/*');
            echo "<div class='check warning'>";
            echo "⚠️ <strong>Note:</strong> Livewire 3 doesn't need published assets!<br>";
            echo "Files found: " . count($files) . " (can be deleted)<br>";
            echo "Livewire serves JS via route: /livewire/livewire.js";
            echo "</div>";
        } else {
            echo check("✅ Good! No static Livewire files (Livewire 3 uses routes)", true);
        }
        
        // === WRITABLE PATHS ===
        echo "<h2>4. Writable Directories</h2>";
        
        $paths = [
            '../storage/framework/cache',
            '../storage/framework/sessions', 
            '../storage/framework/views',
            '../storage/logs',
            '../bootstrap/cache',
        ];
        
        foreach ($paths as $path) {
            $fullPath = __DIR__.'/'.$path;
            $exists = file_exists($fullPath);
            $writable = $exists && is_writable($fullPath);
            
            if (!$exists) {
                echo check("$path: Directory missing", false);
            } else {
                echo check("$path", $writable);
                if (!$writable) {
                    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                    echo "<div class='check error'>Permissions: $perms (should be 775 or 777)</div>";
                }
            }
        }
        
        // === PHP CONFIGURATION ===
        echo "<h2>5. PHP Configuration</h2>";
        
        echo info("PHP Version", PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>='));
        echo info("PHP SAPI", php_sapi_name());
        echo info("Document Root", $_SERVER['DOCUMENT_ROOT'] ?? 'Not set');
        echo info("Server Software", $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
        
        // === PHP EXTENSIONS ===
        echo "<h2>6. PHP Extensions</h2>";
        
        $requiredExtensions = [
            'pdo' => 'PDO',
            'pdo_sqlite' => 'PDO SQLite', 
            'mbstring' => 'Mbstring',
            'openssl' => 'OpenSSL',
            'json' => 'JSON',
            'curl' => 'cURL',
            'zip' => 'ZIP',
        ];
        
        foreach ($requiredExtensions as $ext => $name) {
            echo check("$name extension", extension_loaded($ext));
        }
        
        // === FUNCTIONS ===
        echo "<h2>7. PHP Functions</h2>";
        
        $requiredFunctions = [
            'symlink' => 'symlink() for storage:link',
            'proc_open' => 'proc_open() for Artisan commands',
            'exec' => 'exec() for shell commands',
        ];
        
        foreach ($requiredFunctions as $func => $desc) {
            $exists = function_exists($func);
            echo check($desc, $exists, $exists ? null : 'warning');
        }
        
        // === LARAVEL ROUTES ===
        echo "<h2>8. Laravel Routes Test</h2>";
        
        echo "<div class='check'>";
        echo "Test Livewire route: <a href='/livewire/livewire.js?id=test' target='_blank'>/livewire/livewire.js</a><br>";
        echo "<small>Should return JavaScript code (not 404)</small>";
        echo "</div>";
        
        // === RECOMMENDATIONS ===
        echo "<h2>9. Recommendations</h2>";
        
        $issues = [];
        
        if (!$symlinkExists || !$isSymlink) {
            $issues[] = "❌ Run <code>php artisan storage:link</code> on hosting";
        }
        
        if (!$htaccessExists) {
            $issues[] = "❌ Upload <code>public/.htaccess</code> file";
        }
        
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $issues[] = "⚠️ Upgrade PHP to 8.2 or higher";
        }
        
        if (!function_exists('symlink')) {
            $issues[] = "⚠️ Enable symlink() function in hosting";
        }
        
        if (empty($issues)) {
            echo "<div class='check success'>";
            echo "✅ <strong>All checks passed!</strong><br>";
            echo "Environment looks good. If forms still not working, check:<br>";
            echo "• Browser console for JavaScript errors<br>";
            echo "• Laravel logs: storage/logs/laravel.log<br>";
            echo "• Clear caches: php artisan optimize:clear";
            echo "</div>";
        } else {
            echo "<div class='check error'>";
            echo "<strong>Issues Found:</strong><br>";
            foreach ($issues as $issue) {
                echo "• $issue<br>";
            }
            echo "</div>";
        }
        
        // === HELPER FUNCTIONS ===
        function check($label, $status, $type = null) {
            if ($type === null) {
                $type = $status ? 'success' : 'error';
            }
            
            $icon = match($type) {
                'success' => '✅',
                'error' => '❌',
                'warning' => '⚠️',
                default => 'ℹ️'
            };
            
            return "<div class='check $type'><span class='icon'>$icon</span> $label</div>";
        }
        
        function info($label, $value, $highlight = null) {
            $class = 'check';
            if ($highlight === true) $class .= ' success';
            if ($highlight === false) $class .= ' warning';
            
            return "<div class='$class'><strong>$label:</strong> $value</div>";
        }
        ?>
        
        <h2>10. Next Steps</h2>
        <div class="check">
            <strong>After fixing issues above:</strong><br>
            1. Clear all caches: <code>php artisan optimize:clear</code><br>
            2. Hard refresh browser: <code>Ctrl + Shift + R</code><br>
            3. Check browser console (F12) for JavaScript errors<br>
            4. Test Purchase Request Create page<br>
            5. Share this page output with developer if still broken
        </div>
        
        <hr style="margin: 40px 0;">
        <p style="text-align: center; color: #6b7280; font-size: 14px;">
            Generated by Hosting Diagnostic Script • <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</body>
</html>
