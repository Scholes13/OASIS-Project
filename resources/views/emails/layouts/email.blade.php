<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Werkudara Group - Purchase Request System')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @if(! empty($darkModePreview))
        <style>
            body { background-color: #111827 !important; }
            h1, h2, p, td, strong { color: #f8fafc !important; }
            a { color: #38bdf8 !important; }
            a[style*="background-color: #0ea5e9"] { background-color: #38bdf8 !important; color: #082f49 !important; text-decoration: none !important; box-shadow: 0 12px 24px rgba(56, 189, 248, 0.22) !important; }
            a[style*="background-color: #1e40af"] { background-color: #3b82f6 !important; color: #ffffff !important; text-decoration: none !important; }
            a[style*="background-color: #ffffff"] { background-color: #273244 !important; color: #e5e7eb !important; border-color: #475569 !important; text-decoration: none !important; }
            table[style*="background-color: #eef2f7"] { background-color: #111827 !important; }
            table[style*="background-color: #ffffff"] { background-color: #1f2937 !important; box-shadow: 0 24px 55px rgba(0, 0, 0, 0.3) !important; }
            table[style*="background-color: #eaf8ff"] { background-color: #172033 !important; border: 1px solid #334155 !important; }
            table[style*="background-color: #fff7ed"] { background-color: #2d2214 !important; border: 1px solid #92400e !important; }
            table[style*="background-color: #0ea5e9"], table[style*="background-color: #1e3a8a"] { background-color: #1e40af !important; }
            td[style*="background-color: #f8f9fa"] { background-color: #1f2937 !important; border-top-color: #334155 !important; }
            table[style*="background-color: #0ea5e9"] p, table[style*="background-color: #1e3a8a"] p { color: #ffffff !important; }
            td[style*="background-color: #e0f2fe"] { background-color: transparent !important; color: #f8fafc !important; }
            td[style*="color: #64748b"], p[style*="color: #64748b"], p[style*="color: #475569"] { color: #cbd5e1 !important; }
            td[style*="color: #0f172a"], h2[style*="color: #0f172a"], p[style*="color: #0f172a"], strong[style*="color: #0f172a"] { color: #f8fafc !important; }
            td[style*="color: #ef4444"], strong[style*="color: #dc2626"] { color: #fb7185 !important; }
            td[style*="color: #0284c7"], a[style*="color: #0284c7"] { color: #38bdf8 !important; }
            td[style*="color: #9a3412"], strong[style*="color: #9a3412"] { color: #fed7aa !important; }
            td[style*="border-top: 1px solid #bae6fd"] { border-top-color: #334155 !important; }
        </style>
    @endif
    <!--[if mso]>
    <style type="text/css">
        .button { padding: 14px 32px !important; }
    </style>
    <![endif]-->
</head>
<body style="font-family: 'Plus Jakarta Sans', Arial, sans-serif; line-height: 1.6; color: #111827; margin: 0; padding: 0; background-color: #eef2f7;">
    @if(! empty($emailPreviewControls))
        <div style="position: fixed; top: 16px; right: 16px; z-index: 9999; background: #111827; color: #ffffff; border-radius: 999px; padding: 8px 12px; font-size: 13px; box-shadow: 0 8px 20px rgba(0,0,0,0.25);">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                <input id="email-dark-toggle" type="checkbox" style="margin: 0;" @checked(! empty($darkModePreview))>
                Dark mode
            </label>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.getElementById('email-dark-toggle');
                toggle.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    if (toggle.checked) {
                        url.searchParams.set('dark', '1');
                    } else {
                        url.searchParams.delete('dark');
                    }
                    window.location.href = url.toString();
                });
            });
        </script>
    @endif
    <!-- Email Container -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #eef2f7; padding: 32px 0;">
        <tr>
            <td align="center">
                <!-- Main Email Wrapper -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 14px; box-shadow: 0 20px 45px rgba(15,23,42,0.12); overflow: hidden;">
                    
                    @if(empty($hideEmailHeader))
                        <!-- Header -->
                        <tr>
                            <td style="background-color: #2563eb; padding: 34px 24px; text-align: center; color: #ffffff;">
                                <p style="margin: 0 0 8px 0; font-size: 12px; color: #dbeafe; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                                    OASIS
                                </p>
                                <h1 style="margin: 0; font-size: 26px; line-height: 1.25; font-weight: 800; color: #ffffff;">
                                    @yield('header-title', 'Werkudara Group - Purchase Request System')
                                </h1>
                                <p style="margin: 10px 0 0 0; font-size: 14px; color: #dbeafe;">
                                    @yield('header-subtitle', 'Purchase Request Management')
                                </p>
                            </td>
                        </tr>
                    @endif
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 42px 32px;">
                            @yield('content')
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 13px; border-top: 1px solid #e9ecef; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0 0 5px 0; font-weight: 600; color: #495057;">OASIS</p>
                            <p style="margin: 5px 0; color: #6c757d;">This is an automated message. Please do not reply to this email.</p>
                            <p style="margin: 5px 0 0 0; color: #adb5bd; font-size: 12px;">© {{ date('Y') }} Werkudara Group. All rights reserved.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
