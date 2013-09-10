<?php

namespace winmillwill\Composer\Drupal;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ArrayRepository;
use Composer\Package\Version\VersionParser;
use Composer\Util\RemoteFilesystem;
use Composer\Config;
use Buzz\Browser;
use Drupal\PackagistBundle\Parser\Project;
use Drupal\PackagistBundle\Parser\ComposerPackageConvert;

class Repository extends ArrayRepository
{
    public function __construct(array $repoConfig = array())
    {
        /* if (!preg_match('{^https?://}', $repoConfig['url'])) { */
        /*     $repoConfig['url'] = 'http://'.$repoConfig['url']; */
        /* } */

        /* $urlBits = parse_url($repoConfig['url']); */
        /* if (empty($urlBits['scheme']) || empty($urlBits['host'])) { */
        /*     throw new \UnexpectedValueException('Invalid url given for Drupal.org repository: '.$repoConfig['url']); */
        /* } */

        /* $this->url = rtrim($repoConfig['url'], '/'); */
        /* $this->versionParser = new VersionParser(); */
    }

    public function search($query, $mode = 0)
    {
        $browser = new Browser();
        $apiVersions = array(6, 7, 8);
        $mainUrl = 'http://updates.drupal.org/release-history/%s/%d.x';
        $projects = preg_split('{\s+}', $query);
        $packages = array();
        foreach ($apiVersions as $version) {
            foreach ($projects as $projectName) {
                $parts = explode('/', $projectName);
                $projectName = array_pop($parts);
                $response = $browser
                    ->get(sprintf($mainUrl, $projectName, $version));

                $project = new Project($response->getContent());
                $converter = new ComposerPackageConvert($project);
                foreach ($project->getReleases() as $release) {
                    $this->addPackage($converter->ToComposerPackage($release));
                }
            }
        }
        $regex = '{(?:'.implode('|', $projects).')}i';
        $matches = array();
        foreach ($this->getPackages() as $package) {
            $name = $package->getName();
            if (isset($matches[$name])) {
                continue;
            }
            if (preg_match($regex, $name)) {
                $matches[$name] = array(
                    'name' => $package->getPrettyName(),
                );
            }
        }
        return $matches;
    }

    public function findPackage($name, $version)
    {
        $this->search($name);
        return parent::findPackage($name, $version);
    }

    public function findPackages($name, $version = null)
    {
        $this->search($name);
        return parent::findPackages($name, $version);
    }
}
