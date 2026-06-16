<?php

namespace App\Data;

final class CatalogLookup
{
    /**
     * @param  list<int>  $projectIds
     */
    public function __construct(
        public array $projectIds,
    ) {}
}
