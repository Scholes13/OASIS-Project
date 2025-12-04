<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Werkudara Group - Purchase Request System')</title>
    <!--[if mso]>
    <style type="text/css">
        .button { padding: 14px 32px !important; }
    </style>
    <![endif]-->
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 0; background-color: #f4f4f4;">
    <!-- Email Container -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <!-- Main Email Wrapper -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #1e40af; padding: 30px 20px; text-align: center; color: #ffffff; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #ffffff;">
                                @yield('header-title', 'Werkudara Group - Purchase Request System')
                            </h1>
                            <p style="margin: 8px 0 0 0; font-size: 14px; color: #ffffff; opacity: 0.95;">
                                @yield('header-subtitle', 'Purchase Request Management')
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 30px 20px;">
                            @yield('content')
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 13px; border-top: 1px solid #e9ecef; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0 0 5px 0; font-weight: 600; color: #495057;">Werkudara Group</p>
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
