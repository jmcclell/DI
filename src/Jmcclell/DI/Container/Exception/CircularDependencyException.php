<?php
namespace Jmcclell\DI\Container\Exception;

require_once 'ContainerException.php';

/**
 * 
 * CircularDependencyException
 * 
 * Exception to be thrown when a circular dependency is found that cannot be resolved.
 * 
 * This will occur when DependencyA relies on DependencyB and vice versa with both dependencies using
 * constructor injection to inject one another. Since neither can be instantiated, we cannot resolve
 * the dependency.
 */
class CircularDependencyException extends ContainerException
{
    
}
