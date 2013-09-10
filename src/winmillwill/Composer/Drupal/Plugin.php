<?php

namespace winmillwill\Composer\Drupal;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\CommandEvent;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $repository = new Repository();
        $manager = $composer->getRepositoryManager();
        $manager->setRepositoryClass('drupal', get_class($repository));
        $manager->addRepository($repository);
        $composer->setRepositoryManager($manager);
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('onCommand',  0)
            )
        );
    }

    public function onCommand(CommandEvent $event)
    {
        
    }
}
