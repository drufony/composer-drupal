<?php

namespace Bangpound\Composer\Drupal;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\CommandEvent;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Util\RemoteFilesystem;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    private $io;
    /**
     * @var RemoteFilesystem
     */
    private $rfs;

    /**
     * @var VersionParser
     */
    private $versionParser;

    private $project_types = array(
        'Modules' => 'drupal-module',
        'Themes' => 'drupal-theme',
        'Profiles' => 'drupal-profile',
    );

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->rfs = new RemoteFilesystem($io);
        $this->versionParser = new VersionParser();
        $config = $composer->getConfig();
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
        /** @var Composer $composer */
        $composer = $this->composer;
        $repositoryManager = $composer->getRepositoryManager();
        $package = $composer->getPackage();

        $extra = $package->getExtra();
        if (!empty($extra['drupal-projects'])) {
            $projects = is_array($extra['drupal-projects']) ? $extra['drupal-projects'] : array($extra['drupal-projects']);
            $packages = array();
            foreach ($projects as $project) {
                foreach ($this->getRepository($project) as $config) {
                    $packages[] = $config;
                }
            }
            $repo = $repositoryManager->createRepository('package', array(
                'package' => $packages,
            ));
            $repositoryManager->addRepository($repo);
        }
    }

    private function getRepository($query)
    {
        $apiVersions = array(7);
        $mainUrl = 'http://updates.drupal.org/release-history/%s/%d.x';
        $projects = preg_split('{\s+}', $query);
        $packages = array();
        foreach ($apiVersions as $version) {
            foreach ($projects as $projectName) {
                $parts = explode('/', $projectName);
                $projectName = array_pop($parts);

                $url = sprintf($mainUrl, $projectName, $version);

                $result = $this->rfs->getContents($url, $url, false);
                $project = new Project($result);
                foreach ($project->getReleases() as $release) {
                    $package = $this->toRepositoryConfig($project, $release);
                    if ($package) {
                        $packages[] = $package;
                    }
                }
            }
        }

        return $packages;
    }

    public function toRepositoryConfig(Project $project, Release $release)
    {
        // reformat eg. 7.x-3.5 / 7.x-3.x-dev
        $version = $release->getMainVersion() .'.'. $release->getVersion();

        $ns = array(
            'dc' => 'http://purl.org/dc/elements/1.1/'
        );
        $dc = $project->xml->children($ns['dc']);

        try {
            $parser = new VersionParser();
            $parser->normalize($version);
        } catch (\UnexpectedValueException $e) {
            $version = 'dev-'. $version;
        }

        $package = array();
        $package['name'] = 'drupal/'. $project->xml->short_name;
        $package['description'] = (string) $project->xml->title;
        $package['version'] = $version;
        $package['homepage'] = (string) $project->xml->link;

        $package['dist'] = array(
            'type' => 'tar',
            'url' => $release->getDownload(),
            'reference' => $release->getReference(),
        );
        $package['source'] = array(
            'type' => 'git',
            'url' => 'http://git.drupal.org/project/'. $project->xml->short_name .'.git',
            'reference' => $release->getTag(),
        );
        $package['authors'][] = array(
            'name' => (string) $dc->creator,
        );
        $type = $project->getProjectType();

        if (!isset($this->project_types[$type])) {
            throw new \RuntimeException('Unknown project type of ' . $type);
        }
        $package['type'] = $this->project_types[$type];
        $package['requires'] = array(
            'composer/installers' => '*',
        );
        $dev_branch = $release->getMainVersion() .'.x-'. $release->getMajorVersion() .'.x';

        $package['extra'] = array(
            'branch-alias' => array(
                'dev-'. $dev_branch => $release->getMainVersion() . '.' . $release->getMajorVersion() .'.x-dev',
            ),
        );

        return $package;
    }
}
