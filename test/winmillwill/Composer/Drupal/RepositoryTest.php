<?php

namespace winmillwill\Composer\Drupal;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider searchQueries
     */
    public function testSearch($query)
    {
        $this->assertEquals(
            array('drupal/views' => array('name' => 'drupal/views')),
            $this->createRepository()->search('drupal/views')
        );
        $packages = $this->repository->getPackages();
        $this->assertNotEmpty($packages);
        $this->assertContainsOnlyInstancesOf(
            'Composer\Package\PackageInterface',
            $packages
        );
    }

    public function searchQueries()
    {
        return array(
            array('views'),
            array('drupal/views'),
        );
    }

    public function testRepositoryRead()
    {
        $repository = $this->createRepository();
        $expectedPackages = array(
          array(
            'name' => 'drupal/views',
            'version' => '6.3.0.0'
          ),
          array(
            'name' => 'drupal/views',
            'version' => '6.3.0'
          ),
        );
        foreach ($expectedPackages as $package) {
            $this->assertInstanceOf(
                'Composer\Package\PackageInterface',
                $this->repository
                ->findPackage($package['name'], $package['version'])
            );
        }
    }

    public function createRepository()
    {
        $io = $this->getMockBuilder('Composer\IO\IOInterface')
            ->getMock();
        $config = new \Composer\Config();
        $repoConfig = array('url' => 'updates.drupal.org');
        $this->repository = new Repository($repoConfig, $io, $config);

        return $this->repository;
    }
}
