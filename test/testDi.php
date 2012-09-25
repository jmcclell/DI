<?php
require_once '../src/Jmcclell/DI/Container/Container.php';
require_once '../src/Jmcclell/DI/Container/Configuration/ArrayLoader.php';

use Jmcclell\DI\Container\Container;
use Jmcclell\DI\Container\Configuration\ArrayLoader;


class A
{
    public function getName() {
        return __CLASS__;
    }
}

class B
{
    protected $a;
    
    public function __construct(A $a) {
        $this->a = $a;    
    }
    
    public function getA() {
        return $this->a;
    }
    
    public function getName() {
        return __CLASS__;
    }
}

class C
{
    protected $b;
    
    public function __construct(B $b) {
        $this->b = $b;
    }
    
    public function getB() {
        return $this->b;
    }
    
    public function getName() {
        return __CLASS__;
    }
}

class D
{
    protected $b;
    protected $e;
    
    public function setB(B $b) {
        $this->b = $b;
    }
    
    public function setE(E $e) {
        $this->e = $e;
    }
    
    public function getB() {
        return $this->b;
    }
    
    public function getE() {
        return $this->e;
    }    
    
    public function getName() {
        return __CLASS__;
    }
    
}

class E
{
    protected $name;
    
    public function __construct($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }
}

class Factory {
    static public function getInstanceE($name) {
        return new E($name);
    }
}

class LoopA {
    protected $loopB;
    
    public function __construct(LoopB $loopB) {
        $this->loopB = $loopB;
    }
}

class loopB {
    protected $loopA;
    
    public function __construct(LoopA $loopA) {
        $this->loopA = $loopA;
    }
}

class LoopC {
    protected $loopD;
    
    public function setLoopD(LoopD $loopD) {
        $this->loopD = $loopD;
    }   
    
    public function getName() {
        return __CLASS__;
    } 
}

class LoopD {
    protected $loopC;
    
    public function setLoopC(LoopC $loopC) {
        $this->loopC = $loopC;
    }
    
    public function getName() {
        return __CLASS__;
    }
}

$config = array(
    'dependencies' => array(
        'a' => array(
            'class' => 'A'
        ),
        'b' => array(
            'class' => 'B',
            'constructorInjection' => array('@a')            
        ),
        'c' => array(
            'class' => 'C',
            'constructorInjection' => array('@b')
        ),
        'd' => array(
            'class' => 'D',
            'setterInjection' => array(
                'setB' => array('@b'),
                'setE' => array('@e')
            )
        ),
        'e' => array(
            'class' => 'E',
            'factory' => array(
                'class' => 'Factory',
                'method' => 'getInstanceE',
                'methodArgs' => array('name' => 'namedE')
            )
        ),
        'loopA' => array(
            'class' => 'LoopA',
            'constructorInjection' => array('@loopB')
        ),
        'loopB' => array(
            'class' => 'LoopB',
            'constructorInjection' => array('@loopA')
        ),
        'loopC' => array(
            'class' => 'LoopC',
             'setterInjection' => array(
                 'setLoopD' => array('@loopD')
             )
        ),
        'loopD' => array(
            'class' => 'LoopD',
            'setterInjection' => array(
                'setLoopC' => array('@loopC')
            )
        )
    )
);

$configLoader = new ArrayLoader($config);

$container = new Container($configLoader);

$a = $container->get('a');
$b = $container->get('b');
$c = $container->get('c');
$d = $container->get('d');
$e = $container->get('e');
echo "\n";
echo $a->getName() . "\n";
echo $b->getName() . "\n";
echo $c->getName() . "\n";
echo $d->getName() . "\n";
echo $e->getName() . "\n";

echo "\n";
echo $b->getName() . '::' . $b->getA()->getName() . "\n";
echo $c->getName() . '::' . $c->getB()->getName() . "\n";
echo $d->getName() . '::' . $d->getB()->getName() . '::' . $d->getE()->getName() . "\n";

$loopC = $container->get('loopC');
$loopD = $container->get('loopD');

echo $loopC->getName() . "\n";
echo $loopD->getName() . "\n";



$loopA = $container->get('loopA');
