<?php

namespace winmillwill\Composer\Drupal

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $repository = new Repository();
        $manager = $composer->getRepositoryManger();
        $manager->addRepository($repository);
        $manager->setRepositoryClass('drupal', get_class($repository));
    }
}
