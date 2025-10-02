<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Debug - {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #007cba; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        pre { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 14px; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; color: white; }
        .status.success { background: #28a745; }
        .status.error { background: #dc3545; }
        .refresh { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Authentication Debug Dashboard</h1>
        <p><strong>Environment:</strong> {{ $debugInfo['environment'] }}</p>
        <p><strong>Timestamp:</strong> {{ $debugInfo['timestamp'] }}</p>
        
        <a href="{{ url()->current() }}?format=json" class="refresh">View as JSON</a>
        <a href="{{ url()->current() }}" class="refresh">Refresh Debug</a>

        <h2>🔐 Authentication Status</h2>
        <div class="section {{ $debugInfo['authentication']['check'] ? 'success' : 'error' }}">
            <p><strong>Auth Check:</strong> 
                <span class="status {{ $debugInfo['authentication']['check'] ? 'success' : 'error' }}">
                    {{ $debugInfo['authentication']['check'] ? 'AUTHENTICATED' : 'NOT AUTHENTICATED' }}
                </span>
            </p>
            <p><strong>User ID:</strong> {{ $debugInfo['authentication']['id'] ?? 'null' }}</p>
            <p><strong>User Name:</strong> {{ $debugInfo['authentication']['user_name'] ?? 'null' }}</p>
            <p><strong>User Email:</strong> {{ $debugInfo['authentication']['user_email'] ?? 'null' }}</p>
        </div>

        <h2>🛡️ Guards Status</h2>
        @foreach($debugInfo['authentication']['guards'] as $guardName => $guardInfo)
        <div class="section {{ isset($guardInfo['error']) ? 'error' : ($guardInfo['check'] ? 'success' : 'warning') }}">
            <strong>{{ $guardName }} Guard:</strong>
            @if(isset($guardInfo['error']))
                <span class="status error">ERROR</span> - {{ $guardInfo['error'] }}
            @else
                <span class="status {{ $guardInfo['check'] ? 'success' : 'error' }}">
                    {{ $guardInfo['check'] ? 'ACTIVE' : 'INACTIVE' }}
                </span>
                - ID: {{ $guardInfo['id'] ?? 'null' }}, Name: {{ $guardInfo['user_name'] ?? 'null' }}
            @endif
        </div>
        @endforeach

        <h2>📊 Session Information</h2>
        <div class="section">
            <p><strong>Session Driver:</strong> {{ $debugInfo['session']['driver'] }}</p>
            <p><strong>Session ID:</strong> {{ $debugInfo['session']['id'] }}</p>
            <p><strong>Auth Session Key:</strong> {{ $debugInfo['session']['auth_session_key'] ?? 'null' }}</p>
            <p><strong>Current Business Unit:</strong> {{ $debugInfo['session']['current_business_unit_id'] ?? 'null' }}</p>
            <p><strong>Current Department:</strong> {{ $debugInfo['session']['current_department_id'] ?? 'null' }}</p>
            <p><strong>Session Keys:</strong> {{ count($debugInfo['session']['all_session_keys']) }} total</p>
        </div>

        @if($debugInfo['user_details'])
        <h2>👤 User Details</h2>
        <div class="section success">
            <p><strong>ID:</strong> {{ $debugInfo['user_details']['id'] }}</p>
            <p><strong>Name:</strong> {{ $debugInfo['user_details']['name'] }}</p>
            <p><strong>Email:</strong> {{ $debugInfo['user_details']['email'] }}</p>
            <p><strong>Primary Department ID:</strong> {{ $debugInfo['user_details']['primary_department_id'] ?? 'null' }}</p>
            <p><strong>Global Role:</strong> {{ $debugInfo['user_details']['global_role'] ?? 'null' }}</p>
            <p><strong>Created:</strong> {{ $debugInfo['user_details']['created_at'] }}</p>
        </div>
        @endif

        @if($debugInfo['department_info'])
        <h2>🏢 Department Information</h2>
        <div class="section success">
            @if(isset($debugInfo['department_info']['found_by']))
                <p><strong>Found by:</strong> {{ $debugInfo['department_info']['found_by'] }}</p>
            @endif
            <p><strong>Department ID:</strong> {{ $debugInfo['department_info']['id'] }}</p>
            <p><strong>Department Name:</strong> {{ $debugInfo['department_info']['name'] }}</p>
            <p><strong>Department Code:</strong> {{ $debugInfo['department_info']['code'] }}</p>
            <p><strong>Business Unit ID:</strong> {{ $debugInfo['department_info']['business_unit_id'] }}</p>
            @if(isset($debugInfo['department_info']['business_unit_name']))
                <p><strong>Business Unit Name:</strong> {{ $debugInfo['department_info']['business_unit_name'] }}</p>
            @endif
        </div>
        @else
        <h2>🏢 Department Information</h2>
        <div class="section error">
            <p><strong>Status:</strong> <span class="status error">NO DEPARTMENT FOUND</span></p>
            <p>User does not have a primary department or department relationship is broken.</p>
        </div>
        @endif

        <h2>🔧 Raw Debug Data</h2>
        <div class="section">
            <pre>{{ json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <h2>💡 Troubleshooting Tips</h2>
        <div class="section">
            <ul>
                <li>If authentication shows as false, check if user is properly logged in</li>
                <li>If department info is missing, check user's primary_department_id in database</li>
                <li>If session data is empty, check session driver configuration</li>
                <li>Compare this data between local and hosting environments</li>
            </ul>
        </div>
    </div>
</body>
</html>