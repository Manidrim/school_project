<?php

declare(strict_types=1);

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

return [
    FrameworkBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    MercureBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    ApiPlatformBundle::class => ['all' => true],
    NelmioCorsBundle::class => ['all' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    MakerBundle::class => ['dev' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true, 'test' => true],
];
