<?php

namespace winmillwill\Composer\Drupal;

class Release
{
    public function __construct(\SimpleXMLElement $release)
    {
        // convert to array for better handle
        $this->release = json_decode(json_encode($release), true);

    }

    public function getDownload()
    {
        return $this->release['download_link'];
    }

    public function getHomepage()
    {
        return $this->release['release_link'];
    }

    public function getName()
    {
        return $this->release['name'];
    }

    public function getReference()
    {
        return $this->release['mdhash'];
    }

    public function getTime()
    {
        return $this->release['date'];
    }

    public function getVersion()
    {
        return substr($this->release['version'], 4);
    }

    public function getMainVersion()
    {
        return $this->release['version'][0];
    }

    public function getMajorVersion()
    {
        return $this->release['version_major'][0];
    }

    public function getTag()
    {
        return $this->release['tag'];
    }

    public function isDev()
    {
        return isset($this->release['version_extra']) ? $this->release['version_extra'] === 'dev' : false;
    }

}
