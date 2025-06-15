<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defender360 ITSM Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl w-full">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Defender360 ITSM Platform</h1>
            <p class="text-gray-600 mb-6">Phase 1 - Development Environment</p>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-green-50 p-4 rounded">
                    <h2 class="font-semibold text-green-800">Services Running</h2>
                    <ul class="text-sm text-green-600 mt-2">
                        <li>✓ PostgreSQL 16</li>
                        <li>✓ Redis 7</li>
                        <li>✓ Nginx</li>
                        <li>✓ Laravel {{ app()->version() }}</li>
                    </ul>
                </div>
                
                <div class="bg-blue-50 p-4 rounded">
                    <h2 class="font-semibold text-blue-800">Multi-tenancy</h2>
                    <ul class="text-sm text-blue-600 mt-2">
                        <li>✓ 2 Tenants seeded</li>
                        <li>✓ Tenant isolation ready</li>
                        <li>✓ Domain routing configured</li>
                    </ul>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded mb-6">
                <h2 class="font-semibold text-gray-800 mb-2">Next Steps</h2>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Run installation script: <code class="bg-gray-200 px-1">./scripts/install.sh</code></li>
                    <li>• Access Horizon: <a href="/horizon" class="text-blue-500 hover:underline">http://localhost:8000/horizon</a></li>
                    <li>• Configure Auth0 integration</li>
                    <li>• Create first incident workflow</li>
                </ul>
            </div>
            
            <div class="text-center text-sm text-gray-500">
                <p>Built with Laravel {{ app()->version() }} | PHP {{ PHP_VERSION }}</p>
            </div>
        </div>
    </div>
</body>
</html>