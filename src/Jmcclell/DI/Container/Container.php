<?php
namespace Jmcclell\DI\Container;

require_once 'Exception/DependencyNotFoundException.php';
require_once 'Exception/CircularDependencyException.php';

/**
 * 
 * Container
 * 
 * A very basic dependency injection container for singleton scoped objects.
 * 
 * TODO Current use of reflection classes is probably not the best performance-wise.
 * TODO Currently we resolve dependencies when requested - would it be better to resolve them immediately to catch
 *      errors early on?
 *
 *
 */
class Container
{
   
    /**
     * Array of Dependency objects
     */
    protected $dependencies = array();
    
    /**
     * Array of depdency instances
     */
    protected $instances = array();
    
    /**
     * Stack used to keep track of dependency instantiation. Used to detect circular references
     * when two depdencies that rely on eachother do so via constructor injection, leading to what
     * would otherwise be an infite loop.
     */
    protected $instantiationStack = array();
       
    /**
     * Constructor
     * 
     * @param ConfigurationLoaderInterface $configurationLoader Configuration loader to pass dependency configuration into the container
     */
    public function __construct(Configuration\ConfigurationLoaderInterface $configurationLoader) {
        $this->dependencies = $configurationLoader->getConfiguration()->getDependencies();
    }
    
    /**
     * Returns a dependency with the given name
     * 
     * If the dependency is not already instantiated, it instantiates it and runs any required setter injection methods.
     * 
     * @param String $name The name of the dependency to retrieve
     * @throws DependencyNotFoundException when no dependency can be found with the given name
     */
    public function get($name) {
        if (!array_key_exists($name, $this->dependencies)) {
            throw new Exception\DependencyNotFoundException("Could not locate dependency with name: '$name'");   
        }
        
        if (!isset($this->instances[$name])) {
            $dependency = $this->dependencies[$name];
            $this->instances[$name] = $this->_getInstance($dependency);
            
            foreach ($dependency->getSetterCalls() as $method => $args) {
                $resolvedArgs = $this->_resolveArgs($args);
                call_user_func_array(array($this->instances[$name], $method), $resolvedArgs);
            }           
        }
        
        return $this->instances[$name];
    }
    
    /**
     * Tests whether or not the container has a dependency with the given name
     * 
     * @param String $name The name of the dependency to check for
     * @return Boolean Whether or not the dependency exists
     */
    public function has($name) {
        return isset($this->dependencies[$name]);
    }
    
    /**
     * Creates and returns an instance of this dependency, injecting all required constructor dependencies from the given container
     * 
     * This method keeps track of the dependency instantation stack and detects circular references that cannot be resolved.
     * 
     * @param Dependency $dependency The dependency to instantiate
     * @return Object An object of type $className
     */
    private function _getInstance(Dependency\Dependency $dependency) {
        if(in_array($dependency->getClassName(), $this->instantiationStack)) {
            throw new Exception\CircularDependencyException("Cannot instantiate dependency. Circular dependency found that cannot be resolved. Consider switching to setter injection to avoid this problem.");
        }
        
        array_push($this->instantiationStack, $dependency->getClassName());        
        $instance = $this->_instantiate($dependency);
        array_pop($this->instantiationStack);
        
        return $instance;
    }
    
    /**
     * Instantiates the dependency, injecting constructor arguments as needed.
     * 
     * @param Dependency The dependency to instantiate
     * @return Object an object of type $className
     */
    private function _instantiate(Dependency\Dependency $dependency) {
        if ($dependency->hasFactory()) {
            $refMethod = new \ReflectionMethod($dependency->getFactoryClassName(), $dependency->getFactoryMethod());
            $resolvedFactoryArgs = $this->_resolveArgs($dependency->getFactoryMethodArgs());
            $instance = $refMethod->invokeArgs(null, $resolvedFactoryArgs);
        } else {
            $refClass = new \ReflectionClass($dependency->getClassName());
            $resolvedConstructorArgs = $this->_resolveArgs($dependency->getConstructorArgs());
            $instance = $refClass->newInstanceArgs($resolvedConstructorArgs);
        }
        
        return $instance;
    }
    
    /**
     * Resolves arguments using the Container.
     * 
     * Arguments that are a string in the format @dependency_name will be resolved
     * through the container. All other args simply pass through.
     * 
     * TODO This is really basic and, of course, there's a use case where if we actually wanted to pass
     *      a string literal starting with @ we would be out of luck if the name matched a dependency name
     * 
     * @param Array An array of raw arguments
     * @return Array An array of resolved arguments
     */
    private function _resolveArgs(Array $args) {
        $resolvedArgs = array();
        foreach ($args as $value) {
            $resolvedValue = $value;
            if (is_string($value) && strlen($value)) {
                if($value[0] == '@') {
                    $dependencyName = substr($value, 1);
                    if($this->has($dependencyName)) {
                        $resolvedValue = $this->get($dependencyName);
                    }
                }   
            }
            $resolvedArgs[] = $resolvedValue;
        }  
        
        return $resolvedArgs; 
    }

}
