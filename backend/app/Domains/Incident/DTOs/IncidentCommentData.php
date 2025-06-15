<?php

namespace App\Domains\Incident\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class IncidentCommentData extends Data
{
    public function __construct(
        #[Required]
        public string $comment,
        
        public bool $is_internal = false,
        
        public array $mentioned_users = [],
    ) {}
}