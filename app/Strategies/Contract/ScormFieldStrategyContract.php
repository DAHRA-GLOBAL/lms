<?php

namespace App\Strategies\Contract;

use Peopleaps\Scorm\Entity\ScoTracking;

interface ScormFieldStrategyContract
{
    public function getField(string $key): ?string;

    public function getCmiData(?ScoTracking $track = null): array;
}
