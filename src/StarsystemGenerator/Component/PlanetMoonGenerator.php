<?php

namespace Stu\StarsystemGenerator\Component;

use Stu\StarsystemGenerator\Config\SystemConfigurationInterface;
use Stu\StarsystemGenerator\Lib\PointInterface;
use Stu\StarsystemGenerator\Lib\StuRandom;
use Stu\StarsystemGenerator\SystemMapDataInterface;

final class PlanetMoonGenerator implements PlanetMoonGeneratorInterface
{
    private PlanetPlacementInterface $planetPlacement;

    private StuRandom $stuRandom;

    public function __construct(
        PlanetPlacementInterface $planetPlacement,
        StuRandom $stuRandom
    ) {
        $this->planetPlacement = $planetPlacement;
        $this->stuRandom = $stuRandom;
    }

    public function generate(
        SystemMapDataInterface $mapData,
        SystemConfigurationInterface $config,
    ): void {

        $planetAmount = $this->getPlanetAmount($mapData, $config);

        $moonAmount = $this->getMoonAmount($mapData, $config);

        $planetDisplays = [];

        while ($planetAmount > 0) {
            $planetDisplays[] = $this->planetPlacement->placePlanet($planetAmount, $mapData, $config);
        }
        while ($moonAmount > 0) {
            $this->placeMoon($moonAmount, $planetDisplays);
        }
    }

    /** 
     * @param array<int, array<int, PointInterface>> $planetDisplays
     */
    private function placeMoon(int &$moonAmount, array $planetDisplays): void
    {
        $moonAmount--;
    }

    private function getPlanetAmount(
        SystemMapDataInterface $mapData,
        SystemConfigurationInterface $config
    ): int {
        if (!$config->hasPlanets()) {
            return 0;
        }

        $maxFromConfig = $config->getMaxPlanets();
        $planetAmount = $mapData->getRandomPlanetAmount($this->stuRandom);

        return min($maxFromConfig, $planetAmount);
    }

    private function getMoonAmount(
        SystemMapDataInterface $mapData,
        SystemConfigurationInterface $config
    ): int {
        if (!$config->hasMoons()) {
            return 0;
        }

        $maxFromConfig = $config->getMaxMoons();
        $moonAmount = $mapData->getRandomMoonAmount($this->stuRandom);

        return min($maxFromConfig, $moonAmount);
    }
}
