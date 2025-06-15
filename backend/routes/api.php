<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConfigurationItemController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\KnowledgeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Middleware\Auth0Middleware;

// Health check routes (no auth required)
Route::get('/health', [HealthController::class, 'health']);
Route::get('/status', [HealthController::class, 'status']);
Route::get('/metrics', [HealthController::class, 'metrics']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Temporary dev routes without auth
Route::prefix('v1')->group(function () {
    // Dashboard Routes
    Route::get('dashboard/metrics', function() {
        return response()->json([
            'total_incidents' => 145,
            'open_incidents' => 32,
            'overdue_incidents' => 5,
            'sla_compliance' => 94,
            'incidents_today' => 8,
            'avg_resolution_time' => 4.5
        ]);
    });
    
    Route::get('dashboard/recent-incidents', function() {
        return response()->json([
            [
                'id' => '1',
                'number' => 'INC-2024-001',
                'title' => 'Email server connectivity issue',
                'priority' => 'high',
                'status' => 'in_progress',
                'assigned_user' => [
                    'id' => '1',
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ],
                'created_at' => '2024-01-14T10:30:00Z',
                'sla_response_target' => '2024-01-14T12:30:00Z'
            ]
        ]);
    });
});

// API V1 Routes with Auth0 authentication
Route::prefix('v1')->middleware(Auth0Middleware::class)->group(function () {
    
    // Auth Routes
    Route::get('me', [AuthController::class, 'me']);
    
    // Incident Management Routes
    Route::apiResource('incidents', IncidentController::class);
    Route::prefix('incidents/{incident}')->group(function () {
        Route::post('assign', [IncidentController::class, 'assign']);
        Route::post('comments', [IncidentController::class, 'addComment']);
        Route::get('comments', [IncidentController::class, 'comments']);
        Route::post('attachments', [IncidentController::class, 'uploadAttachment']);
        Route::patch('status', [IncidentController::class, 'updateStatus']);
        Route::get('history', [IncidentController::class, 'history']);
    });
    Route::post('incidents/bulk-update', [IncidentController::class, 'bulkUpdate']);
    Route::post('incidents/export', [IncidentController::class, 'export']);
    Route::get('incidents-metrics', [IncidentController::class, 'metrics']);
    
    // Dashboard Routes
    Route::get('dashboard/executive', [DashboardController::class, 'executive']);
    Route::get('dashboard/operational', [DashboardController::class, 'operational']);
    Route::get('dashboard/team', [DashboardController::class, 'team']);
    Route::get('dashboard/sla-performance', [DashboardController::class, 'slaPerformance']);
    
    // Analytics Routes
    Route::prefix('analytics')->group(function () {
        Route::get('incidents', [AnalyticsController::class, 'incidents']);
        Route::get('changes', [AnalyticsController::class, 'changes']);
        Route::get('sla', [AnalyticsController::class, 'sla']);
        Route::get('workload', [AnalyticsController::class, 'workload']);
        Route::get('predictive', [AnalyticsController::class, 'predictive']);
        Route::get('realtime', [AnalyticsController::class, 'realtime']);
        Route::post('export', [AnalyticsController::class, 'export']);
        Route::post('schedule-report', [AnalyticsController::class, 'scheduleReport']);
        Route::get('drilldown', [AnalyticsController::class, 'drilldown']);
    });
    
    // Configuration Management (CMDB)
    Route::apiResource('configuration-items', ConfigurationItemController::class);
    Route::get('configuration-items/types', [ConfigurationItemController::class, 'types']);
    Route::get('configuration-items/statuses', [ConfigurationItemController::class, 'statuses']);
    Route::get('configuration-items/{configurationItem}/relationships', [ConfigurationItemController::class, 'relationships']);
    Route::get('configuration-items/{configurationItem}/impact', [ConfigurationItemController::class, 'impact']);
    Route::get('configuration-items/{configurationItem}/maintenance-history', [ConfigurationItemController::class, 'maintenanceHistory']);
    Route::post('configuration-items/export', [ConfigurationItemController::class, 'export']);
    Route::post('configuration-items/bulk-update', [ConfigurationItemController::class, 'bulkUpdate']);
    
    // Customer Portal
    Route::prefix('portal')->group(function () {
        Route::get('stats', [PortalController::class, 'stats']);
        Route::get('tickets', [PortalController::class, 'tickets']);
        Route::get('tickets/{incident}', [PortalController::class, 'showTicket']);
        Route::post('tickets', [PortalController::class, 'createTicket']);
        Route::post('tickets/{incident}/comments', [PortalController::class, 'addComment']);
        Route::get('knowledge/search', [PortalController::class, 'searchKnowledge']);
        Route::get('knowledge/popular', [PortalController::class, 'popularArticles']);
        Route::get('knowledge/{article}', [PortalController::class, 'viewArticle']);
        Route::post('knowledge/{article}/rate', [PortalController::class, 'rateArticle']);
    });
    
    // Knowledge Base Management
    Route::prefix('knowledge')->group(function () {
        // Article management
        Route::apiResource('articles', KnowledgeController::class)
            ->parameters(['articles' => 'knowledgeArticle']);
        
        // Search endpoints
        Route::get('search', [KnowledgeController::class, 'search']);
        Route::get('search/autocomplete', [KnowledgeController::class, 'autocomplete']);
        Route::get('search/trending', [KnowledgeController::class, 'trending']);
        Route::get('search/health', [KnowledgeController::class, 'searchHealth']);
        Route::post('search/reindex', [KnowledgeController::class, 'reindex']);
        
        // Article interactions
        Route::post('articles/{knowledgeArticle}/rate', [KnowledgeController::class, 'rate']);
        Route::post('articles/{knowledgeArticle}/helpful', [KnowledgeController::class, 'markHelpful']);
        Route::get('articles/{knowledgeArticle}/similar', [KnowledgeController::class, 'similar']);
        Route::get('articles/{knowledgeArticle}/versions', [KnowledgeController::class, 'versions']);
        Route::post('articles/{knowledgeArticle}/versions/{version}/restore', [KnowledgeController::class, 'restoreVersion']);
        
        // Categories
        Route::get('categories', [KnowledgeController::class, 'categories']);
        Route::post('categories', [KnowledgeController::class, 'createCategory']);
        
        // Suggestions
        Route::get('suggestions', [KnowledgeController::class, 'suggestions']);
    });
    
    // Notification Management
    Route::prefix('notifications')->group(function () {
        // User notifications
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/statistics', [NotificationController::class, 'statistics']);
        Route::get('/{notification}', [NotificationController::class, 'show']);
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/archive', [NotificationController::class, 'archive']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        
        // Preferences
        Route::get('/preferences', [NotificationController::class, 'preferences']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
        
        // Templates and channels (admin only)
        Route::get('/templates', [NotificationController::class, 'templates']);
        Route::get('/channels', [NotificationController::class, 'channels']);
        Route::post('/channels/test', [NotificationController::class, 'testChannel']);
        
        // Test notification
        Route::post('/test', [NotificationController::class, 'sendTest']);
    });
});