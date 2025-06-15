<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TemplateService;
use App\Services\ChannelService;
use App\Models\Tenant;

class InitializeNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:initialize {--tenant=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize notification templates and channels';

    protected TemplateService $templateService;
    protected ChannelService $channelService;

    public function __construct(TemplateService $templateService, ChannelService $channelService)
    {
        parent::__construct();
        $this->templateService = $templateService;
        $this->channelService = $channelService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing notification system...');

        // Create default system templates
        $this->info('Creating default notification templates...');
        $this->templateService->createDefaultTemplates();
        $this->info('✓ Default templates created');

        // If tenant specified, create tenant-specific resources
        $tenantId = $this->option('tenant');
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found");
                return 1;
            }

            $this->info("Setting up notifications for tenant: {$tenant->name}");

            // Clone system templates for tenant
            $this->info('Cloning system templates for tenant...');
            $clonedTemplates = $this->templateService->cloneSystemTemplatesForTenant($tenantId);
            $this->info("✓ Cloned {$clonedTemplates->count()} templates");

            // Create default channels for tenant
            $this->info('Creating default notification channels...');
            $this->channelService->createDefaultChannels($tenantId);
            $this->info('✓ Default channels created');
        } else {
            // Initialize for all tenants
            $tenants = Tenant::all();
            $bar = $this->output->createProgressBar($tenants->count());
            $bar->start();

            foreach ($tenants as $tenant) {
                // Clone templates
                $this->templateService->cloneSystemTemplatesForTenant($tenant->id);
                
                // Create channels
                $this->channelService->createDefaultChannels($tenant->id);
                
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info('✓ Notification system initialized successfully!');
        return 0;
    }
}