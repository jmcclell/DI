<?php

namespace Jmcclell\DI\Container\Exception;

require_once 'ContainerException.php';

/**
 * 
 * DependencyNotFoundException
 * 
 * Thrown when a dependency cannot be found by name.
 */
class DependencyNotFoundException extends ContainerException
{
    
}
