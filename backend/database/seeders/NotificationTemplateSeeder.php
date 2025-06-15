<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\TemplateService;

class NotificationTemplateSeeder extends Seeder
{
    protected TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->templateService->createDefaultTemplates();
    }
}