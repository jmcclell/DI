<?php

namespace Jmcclell\DI\Container\Dependency;


use Jmcclell\DI\Container\Container;

/**
 * 
 * Dependency
 * 
 * Represents a dependency in the DI container. Holds all meta data used to instantiate an instance.
 */
class Dependency
{
    /**
     * The name of the class this dependency represents
     */
    private $className;
    
    /**
     * A list of ordered constructor arguments to pass when instantiating the dependency
     */
    private $constructorArgs;
    
    /**
     * A list of setter calls to invoke immediately after instantiating the dependency
     * 
     * Should be in the form:
     * 
     * Array(
     *     'method' => array('arg1', ...)
     * )
     */
    private $setterCalls;
    
    /**
     * If this dependency should be instantiated via a factory, this property represents the
     * class which contains the factory method. If this is not set but $factoryMethod is, it
     * is assumed that the dependency class itself contains its own factory method.
     */
    private $factoryClassName;
    
    /**
     * The factory method used to instantiate this dependency if one is required
     */
    private $factoryMethod;
    
    /**
     * A list of ordered arguments passed to the factory method if needed
     */
    private $factoryMethodArgs;
    
    /**
     * A boolean value to determine if this dependency should be treated as a singleton or
     * if a new instance should be created each time an instance is requested.
     */
    private $singleton = true;
    
    /**
     * Constructor
     * 
     * @param String $className The name of the class this dependency represents
     * @param Boolean $singleton Whether or not this dependency is a singleton (True by default)
     * @param Array $constructorArgs The arguments to pass to the constructor (optional)
     * @param Array $setterCalls The setter methods to invoke after instantiation (optional)
     * @param String $factoryMethod The method used to instantiate this dependency (optional)
     * @param Array $factoryMethodArgs The arguments to pass to the factory method (optional)
     * @param String $factoryClass The class that contains the factory method (optional)
     * 
     */
    public function __construct($className, $singleton = true, Array $constructorArgs = array(), Array $setterCalls = array(), $factoryMethod = null, Array $factoryMethodArgs = array(), $factoryClassName = null) {
        $this->_setClassName($className);
        $this->_setSingleton($singleton);
        $this->_setConstructorArgs($constructorArgs);
        $this->_setSetterCalls($setterCalls);
        
        if($factoryMethod) {
            $this->_setFactoryClassName($factoryClassName); 
            $this->_setFactoryMethod($factoryMethod);
            $this->_setFactoryMethodArgs($factoryMethodArgs);  
            if (!$factoryClassName) {
                $factoryClassName = $this->className;
            } 
            
        } 
    }
    
    /**
     * Sets the factory class name
     * 
     * @param String @factoryClassName The fully qualfied name of the class containing the factory method
     */
    private function _setFactoryClassName($factoryClassName) {
        if(!class_exists($factoryClassName)) {
            throw new \InvalidArgumentException("Could not locate class: '$factoryClassName'");   
        }
        
        $this->factoryClassName = $factoryClassName;
    }
    
    /**
     * Sets the factory method argument list
     * 
     * @param Array $factoryMethodArgs An array of arguments to pass to the factory method
     */
    private function _setFactoryMethodArgs($factoryMethodArgs) {
        $normalizedFactoryMethodArgs = $this->_normalizeArgList($this->factoryClassName, $this->factoryMethod, $factoryMethodArgs);
        $this->factoryMethodArgs = $factoryMethodArgs;
    }
    
    /**
     * Sets the facatory method
     * 
     * @param String $factoryMethod The name of the factory method to call to get a new instance of the dependency class
     */
    private function _setFactoryMethod($factoryMethod) {
        if(!method_exists($this->factoryClassName, $factoryMethod)) {
            throw new \InvalidArgumentException("Could not find method: " . $this->factoryClassName . "::$factoryMethod");
        }
        
        $this->factoryMethod = $factoryMethod;
    }
    
    /**
     * Creates a normalized list of setter calls to be called immediately after dependency instantiation
     * 
     * @param Array $setterCalls An array outlining the setter calls to be made after instantiation
     */
    private function _setSetterCalls(Array $setterCalls) {
        $normalizedSetterCalls = array();
        foreach($setterCalls as $method => $arguments) {
            $normalizedArguments = $this->_normalizeArgList($this->className, $method, $arguments);
            $normalizedSetterCalls[$method] = $normalizedArguments;
        }
        
        $this->setterCalls = $normalizedSetterCalls;
    }
    
    /**
     * Normalize and set the constructor args
     * 
     * @param Array $constructorArgs An array of arguments to be passed to the constructor during instantiation
     */
    private function _setConstructorArgs(Array $constructorArgs) {
        $constructorArgs = $this->_normalizeArgList($this->className, null, $constructorArgs);
        $this->constructorArgs = $constructorArgs;
    }
    
    /**
     * Given a class and method name, normalize an argument list so that arguments are returned as
     * an ordered array that can be passed to ReflectionMethod::invoke().
     * 
     * Args can be passed to this function such that ordered arguments are at the head of the array
     * and named arguments can follow in any order.
     * 
     * TODO This doesn't properly validate the ordered args first, named args second pattern which might lead to confusion
     * 
     * @param String $classNAme The name of the class
     * @param String $methodName The name of the method. If NULL, __constructor() is assumed. [optional]
     * @param Array $args The arguments to pass to the method
     */
    private function _normalizeArgList($className, $methodName = null, Array $args) {
        $orderedArgs = array();
        
        if($methodName == null) {
            // TODO Is there a way to get the constructor without yet another reflection object?
            $refClass = new \ReflectionClass($className);
            $refMethod = $refClass->getConstructor();
        } else {
            $refMethod = new \ReflectionMethod($className, $methodName);
        }
        
        if (!$refMethod) {
            //TODO If refMethod is null this is a no-arg constructor, so $args should be empty. Do we throw an exception here or let it become a problem later?
            return $args;
        }
        
        $params = $refMethod->getParameters();
        $namedParameters = array();
        foreach ($params as $position => $param) {
            $argName = $param->getName();
            if(array_key_exists($argName, $args)) {
                $orderedArgs[$position] = $args[$argName];
                unset($args[$argName]);
            }
        }

        return array_merge($args, $orderedArgs);
    }
        
    /**
     * Determines whether or not this class should be treated as a singleton or if a new instance should be created
     * each time it is requested.
     * 
     * @param Boolean $singleton Whether or not this Dependency should be treated as a singleton
     */      
    private function _setSingleton($singleton) {
        $this->singleton = (bool)$singleton;
    }                    
                        
    /**
     * Sets the class name for the dependency
     * 
     * Validates that the class exists
     * 
     * @param String $classNAme The name of the dependency class
     * @throws ClassNotFoundException
     */
    private function _setClassName($className) {
        if (!class_exists($className)) {
            throw new Exception\ClassNotFoundException("Could not locate class with name: '$className'.");
        }
        
        $this->className = $className;
    }
    
    /**
     * @return Boolean Whether or not this dependency should be treatd as a singleton
     */
    public function isSingleton() {
        return $this->singleton;
    }
    
    /**
     * @return String The name of the class
     */
    public function getClassName() {
        return $this->className;
    }
    
    /**
     * @return Array The arguments to pass to the constructor upon instantiation
     */
    public function getConstructorArgs() {
        return $this->constructorArgs;
    }
    
    /**
     * @return Array The setter call definitions to call after instantiation
     */
    public function getSetterCalls() {
        return $this->setterCalls;
    }
    
    /**
     * @return String The name of the factory class
     */
    public function getFactoryClassName() {
        return $this->factoryClassName;
    }
    
    /**
     * @return String The name of the factory method
     */
    public function getFactoryMethod() {
        return $this->factoryMethod;
    }
    
    /**
     * @return String Whether or not this dependency should be instantiated by a Factory
     */
    public function hasFactory() {
        return $this->factoryMethod != null;
    }
    
    /**
     * @return Array The arguments to pass to the factory method
     */
    public function getFactoryMethodArgs() {
        return $this->factoryMethodArgs;
    }
}
