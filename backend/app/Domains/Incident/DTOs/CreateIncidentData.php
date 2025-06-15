<?php

namespace App\Domains\Incident\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use App\Domains\Incident\Models\Incident;

class CreateIncidentData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $title,
        
        #[Required]
        public string $description,
        
        #[Required, In([Incident::PRIORITY_LOW, Incident::PRIORITY_MEDIUM, Incident::PRIORITY_HIGH, Incident::PRIORITY_CRITICAL])]
        public string $priority,
        
        #[Required, In([Incident::IMPACT_LOW, Incident::IMPACT_MEDIUM, Incident::IMPACT_HIGH, Incident::IMPACT_ENTERPRISE])]
        public string $impact,
        
        #[Required, In(['low', 'medium', 'high', 'urgent'])]
        public string $urgency,
        
        #[Required, Max(100)]
        public string $category,
        
        #[Max(100)]
        public ?string $subcategory = null,
        
        public ?string $assigned_to = null,
        
        public ?string $assigned_group = null,
        
        public ?string $customer_notes = null,
        
        public array $tags = [],
        
        public array $custom_fields = [],
    ) {}
}