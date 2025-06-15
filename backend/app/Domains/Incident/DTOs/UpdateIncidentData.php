<?php

namespace App\Domains\Incident\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use App\Domains\Incident\Models\Incident;

class UpdateIncidentData extends Data
{
    public function __construct(
        #[Max(255)]
        public string|Optional $title,
        
        public string|Optional $description,
        
        #[In([Incident::STATUS_OPEN, Incident::STATUS_IN_PROGRESS, Incident::STATUS_RESOLVED, Incident::STATUS_CLOSED])]
        public string|Optional $status,
        
        #[In([Incident::PRIORITY_LOW, Incident::PRIORITY_MEDIUM, Incident::PRIORITY_HIGH, Incident::PRIORITY_CRITICAL])]
        public string|Optional $priority,
        
        #[In([Incident::IMPACT_LOW, Incident::IMPACT_MEDIUM, Incident::IMPACT_HIGH, Incident::IMPACT_ENTERPRISE])]
        public string|Optional $impact,
        
        #[In(['low', 'medium', 'high', 'urgent'])]
        public string|Optional $urgency,
        
        #[Max(100)]
        public string|Optional $category,
        
        #[Max(100)]
        public string|Optional $subcategory,
        
        public string|Optional $assigned_to,
        
        public string|Optional $assigned_group,
        
        public string|Optional $resolution_notes,
        
        public string|Optional $customer_notes,
        
        public array|Optional $tags,
        
        public array|Optional $custom_fields,
    ) {}
}