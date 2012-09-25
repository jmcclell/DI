<?php
namespace Jmcclell\DI\Container\Configuration;

/**
 *
 * ConfigurationLoaderInterface 
 *
 * Defines a basic interface for a configuration loader to be passed into the Container
 * 
 */
interface ConfigurationLoaderInterface
{
    /**
     * Return a Configuration instance
     * 
     * @return Container
     */
    public function getConfiguration();
}
