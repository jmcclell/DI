<?php
namespace Jmcclell\DI\Container\Configuration;

require_once 'ConfigurationLoaderInterface.php';
require_once 'Configuration.php';
require_once '../src/Jmcclell/DI/Container/Dependency/Dependency.php';

use Jmcclell\DI\Container\Dependency\Dependency;

/**
 * 
 * ArrayLoader
 *
 * Allows loading configuration for the DI Container via Array
 * 
 * Accepts configuration in the form:
 * 
 * array(
 *     'dependencies' => array('dependency_name' => array(...), ...)
 * )
 * 
 * Each dependency configuration example might look like:
 * 
 * array(
 *     'class' => '\Fully\Qualified\Classname',
 *     'constructorInjection' => array('@depenendcy_for_arg_1', 'static literal string for arg2', 'namedArg' => '@dependency_for_named_arg'),
 *     'setterInjection' => array(
 *         array('method' => 'setDependencyFoo', 'args' => array('@dependencyFoo'))
 *     ),
 *     'factory' => array(
 *         'class' => '\Fully\Qualified\Classname',
 *         'method' => 'methodName',
 *         'methodArgs' => array(...)
 *     )
 * )
 * 
 * When defining arguments, positional arguments are taken in order and should come at the start of the array. Any number of named arguments can
 * come after the positional arguments are defined - if there are any.
 * 
 * The only required parameter is the class name.
 * 
 * If a factory is used, the factory class is optional. If one is not provided, it is assumed that the dependency class itself contains its factory
 * method. Thus, the only required parameter for a facto
 */
class ArrayLoader implements ConfigurationLoaderInterface
{
    /**
     * Array
     */
    private $configurationArray;
    
    /**
     * The Configuration instance created from the incoming configuration array
     */
    private $configuration = null;
    
    /**
     * Constructor
     * 
     * @param Array $configuration The configuration represented in Array form
     */
    public function __construct(Array $configuration) {
        $this->configurationArray = $configuration;  
    }
    
    /**
     * Return the object graph based on the array configuration
     * 
     * @return Configuration
     */
    public function getConfiguration() {
        if ($this->configuration == null) {
            $this->configuration = $this->_processConfigurationArray();
        }

        return $this->configuration;
    }
    
   
    /**
     * Processes the incoming configuration array
     * 
     * TODO Much better validation needs to be added. Friendlier messages and exceptions will make a somewhat confusing array
     *      structure easier to digest for new developers.
     * 
     * @return Configuration
     */
    private function _processConfigurationArray() {
        $configuration = $this->configurationArray;
        if(!array_key_exists('dependencies', $configuration)) {
            throw new \InvalidArgumentException('Required configuraiton parameter missing: dependencies');   
        }
        
        $dependencies = array();
        foreach((array)$configuration['dependencies'] as $name => $dependencyMetaData) {
            if(!isset($dependencyMetaData['class'])) {
                throw new \InvalidArgumentException('Required attribute missing: class');
            }
            $className = $dependencyMetaData['class'];
            $singleton = true; // Container only supports singletons at this time
            $constructorArgs = (isset($dependencyMetaData['constructorInjection'])) ? $dependencyMetaData['constructorInjection'] : array();
            $setterCalls = (isset($dependencyMetaData['setterInjection'])) ? $dependencyMetaData['setterInjection'] : array();
            if(isset($dependencyMetaData['factory'])) {
                $factoryMetaData = $dependencyMetaData['factory'];
            } else {
                $factoryMetaData = array();
            }
            $factoryClassName =(isset($factoryMetaData['class'])) ? $factoryMetaData['class'] : null;
            $factoryMethod = (isset($factoryMetaData['method'])) ? $factoryMetaData['method'] : null;
            $factoryMethodArgs = (isset($factoryMetaData['methodArgs'])) ? $factoryMetaData['methodArgs'] : array();
            $dependencies[$name] = new Dependency($className, $singleton, $constructorArgs, $setterCalls, $factoryMethod, $factoryMethodArgs, $factoryClassName);
        }
        
        
        return new Configuration($dependencies);
        
    }
}
