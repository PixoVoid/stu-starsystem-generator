<?php

use Stu\StarsystemGenerator\Config\SystemConfiguration;

$config = new SystemConfiguration();

$config->setAllowedGrowthPercentage(300);

$config->setMinSize(23);

$config->setHasPlanets(true);

$config->setHasMoons(true);

$config->setHasAsteroids(true);

$config->setMaxPlanets(12);

$config->setMaxMoons(35);

$config->setMaxAsteroids(50);



return $config;
