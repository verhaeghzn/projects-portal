<?php

namespace App\Data;

/**
 * Validated smart-search output applied to the project listing query.
 */
final class SmartSearchCriteria
{
    /**
     * @param  list<int>  $projectIds
     * @param  array<int, string>  $projectReasons
     */
    public function __construct(
        public array $projectIds = [],
        public ?string $summaryForUser = null,
        public array $projectReasons = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->projectIds === [];
    }
}
