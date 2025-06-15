<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Incident Created</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .incident-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .priority {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        .priority-critical { background-color: #721c24; color: white; }
        .priority-high { background-color: #dc3545; color: white; }
        .priority-medium { background-color: #ffc107; color: #212529; }
        .priority-low { background-color: #007bff; color: white; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Incident Created</h1>
        </div>
        
        <div class="content">
            <p>A new incident has been created that requires your attention:</p>
            
            <div class="incident-details">
                <div class="detail-row">
                    <span class="detail-label">Incident Number:</span>
                    <span>#{{ $variables['incident_number'] ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Title:</span>
                    <span>{{ $variables['title'] ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Priority:</span>
                    <span class="priority priority-{{ $variables['priority'] ?? 'medium' }}">
                        {{ $variables['priority'] ?? 'medium' }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Assigned To:</span>
                    <span>{{ $variables['assigned_to'] ?? 'Unassigned' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created By:</span>
                    <span>{{ $variables['created_by'] ?? 'System' }}</span>
                </div>
            </div>
            
            <p><strong>Description:</strong></p>
            <p>{{ $variables['description'] ?? 'No description provided.' }}</p>
            
            <center>
                <a href="{{ $incident_url }}" class="button">View Incident</a>
            </center>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from {{ config('app.name') }}.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>