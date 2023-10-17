<?php

namespace App\Services\Contract;


use Peopleaps\Scorm\Entity\ScoTracking;
use Peopleaps\Scorm\Model\ScormScoTrackingModel;

interface ScormTrackServiceContract
{
    public function getUserResult(int $scoId, int $userId): ?ScormScoTrackingModel;
    public function getUserResultSpecifiedValue(string $key, int $scoId, int $userId);
    public function createScoTracking($scoUuid, $userId = null): ScoTracking;
    public function findScoTrackingId($scoUuid, $scoTrackingUuid);
    public function checkUserIsCompletedScorm($scormId, $userId);
    public function updateScoTracking($scoUuid, $userId, $data);
}
