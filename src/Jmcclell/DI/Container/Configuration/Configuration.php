<?php
namespace Jmcclell\DI\Container\Configuration;

/**
 *
 *  Configuration
 * 
 * Holds configuration data for the Container. As of now the only configuration data consists of
 * the Dependency list, but this object allows for future configuration meta-data unrelated to
 * dependencies to be added.
 * 
 * TODO This may be superfulous and could be axed
 */
class Configuration
{
    /**
     * An array of dependency objects used to configure a Container
     */
    private $dependencies;
    
    /**
     * Constructor
     * 
     * @param Array $dependencies array of Dependency objects
     */
    public function __construct($dependencies) {
        $this->dependencies = $dependencies;
    }
    
    /**
     * @return Array of Dependency objects
     */
    public function getDependencies() {
        return $this->dependencies;
    }
}
