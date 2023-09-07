<?php

namespace Stu\StarsystemGenerator\Component;

use RuntimeException;
use Stu\StarsystemGenerator\Config\PlanetMoonProbabilitiesInterface;
use Stu\StarsystemGenerator\Config\PlanetMoonRange;
use Stu\StarsystemGenerator\Config\PlanetRadius;
use Stu\StarsystemGenerator\Config\SystemConfigurationInterface;
use Stu\StarsystemGenerator\Enum\BlockedFieldTypeEnum;
use Stu\StarsystemGenerator\Enum\FieldTypeEnum;
use Stu\StarsystemGenerator\Exception\PlanetMaximumReachedException;
use Stu\StarsystemGenerator\Lib\Field;
use Stu\StarsystemGenerator\Lib\PlanetDisplayInterface;
use Stu\StarsystemGenerator\Lib\Point;
use Stu\StarsystemGenerator\Lib\PointInterface;
use Stu\StarsystemGenerator\Lib\StuRandom;
use Stu\StarsystemGenerator\SystemMapDataInterface;

final class PlanetPlacement implements PlanetPlacementInterface
{
    public const MAX_TRIED_PLANET_TYPES = 20;
    public const MAX_RETRIES_PER_PLANET_TYPE = 5;

    private PlanetMoonProbabilitiesInterface $planetMoonProbabilities;
    private StuRandom $stuRandom;

    public function __construct(
        PlanetMoonProbabilitiesInterface $planetMoonProbabilities,
        StuRandom $stuRandom
    ) {
        $this->planetMoonProbabilities = $planetMoonProbabilities;
        $this->stuRandom = $stuRandom;
    }

    public function placePlanet(int &$planetAmount, SystemMapDataInterface $mapData, SystemConfigurationInterface $config): PlanetDisplayInterface
    {
        $planetDisplay = null;

        $maxTries = self::MAX_TRIED_PLANET_TYPES;

        $triedPlanetFieldIds = [];

        while ($maxTries > 0) {
            $randomPlanetFieldId = $this->planetMoonProbabilities->pickRandomFieldId(
                $triedPlanetFieldIds,
                $config->getProbabilities(FieldTypeEnum::PLANET),
                $config->getPropabilityBlacklist(FieldTypeEnum::PLANET)
            );
            $triedPlanetFieldIds[] = $randomPlanetFieldId;

            $planetDisplay = $this->tryToFindPlanetDisplay(
                $mapData,
                $randomPlanetFieldId,
                $planetAmount + 1
            );

            if ($planetDisplay !== null) {
                break;
            }

            $maxTries--;
        }

        if ($planetDisplay === null) {
            $this->dumpBothDisplays($mapData);
            throw new PlanetMaximumReachedException(sprintf('could not place any of %d colony classes', self::MAX_TRIED_PLANET_TYPES));
        }

        $planetAmount++;

        $centerPoint = $this->getCenterCoordinate($planetDisplay);

        try {
            $mapData->setField(new Field($centerPoint, $randomPlanetFieldId));
            $mapData->addIdentifier($centerPoint, (string)$planetAmount);
        } catch (RuntimeException $e) {
            //echo $e->getMessage();
            $this->dumpBothDisplays($mapData);

            throw $e;
        }

        //hard block fields left and right if ring planet
        if ((int)($randomPlanetFieldId / 100) === 3) {
            $this->addPlanetRing($randomPlanetFieldId, $centerPoint, $mapData);
        }

        $mapData->blockField($centerPoint, true, FieldTypeEnum::PLANET, BlockedFieldTypeEnum::HARD_BLOCK);

        return $planetDisplay;
    }

    private function addPlanetRing(int $planetFieldId, PointInterface $planetLocation, SystemMapDataInterface $mapData): void
    {
        $leftRingFieldId = $planetFieldId * 10 + 1;
        $rightRingFieldId = $planetFieldId * 10 + 2;

        $leftRingPoint = $planetLocation->getLeft();
        $rightRingPoint = $planetLocation->getRight();

        $mapData->setField(new Field($leftRingPoint, $leftRingFieldId), BlockedFieldTypeEnum::MASS_CENTER_PERIMETER_BLOCK);
        $mapData->setField(new Field($rightRingPoint, $rightRingFieldId), BlockedFieldTypeEnum::MASS_CENTER_PERIMETER_BLOCK);

        $mapData->blockField($leftRingPoint, false, null, BlockedFieldTypeEnum::HARD_BLOCK);
        $mapData->blockField($rightRingPoint, false, null, BlockedFieldTypeEnum::HARD_BLOCK);
    }

    /**
     * @return null|PlanetDisplayInterface
     */
    private function tryToFindPlanetDisplay(
        SystemMapDataInterface $mapData,
        int $randomPlanetFieldId,
        int $planetAmount
    ): ?PlanetDisplayInterface {

        $planetMoonRange = PlanetMoonRange::getPlanetMoonRange($randomPlanetFieldId);

        $planetDisplay = null;

        $maxTries = self::MAX_RETRIES_PER_PLANET_TYPE;
        while ($maxTries > 0) {
            $planetRadiusPercentage = PlanetRadius::getRandomPlanetRadiusPercentage($randomPlanetFieldId, $this->stuRandom);

            $planetDisplay = $mapData->getPlanetDisplay(
                $planetRadiusPercentage,
                $planetMoonRange,
                (string)$planetAmount
            );

            if ($planetDisplay !== null) {
                break;
            }

            $maxTries--;
        }

        return $planetDisplay;
    }

    private function getCenterCoordinate(PlanetDisplayInterface $planetDisplay): PointInterface
    {
        $firstPoint = $planetDisplay->getFirstPoint();
        $lastPoint = $planetDisplay->getLastPoint();

        return new Point(($firstPoint->getX() + $lastPoint->getX()) / 2,
            ($firstPoint->getY() + $lastPoint->getY()) / 2
        );
    }

    private function dumpBothDisplays(SystemMapDataInterface $mapData): void
    {
        echo "FAIL";
        echo "<br>";
        echo $mapData->toString(true);
        echo "<br>";
        echo $mapData->toString(true, true);
    }
}
