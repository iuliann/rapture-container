<?php

use Rapture\Container\Container;

class RaptureContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testReflect()
    {
        $container = new Container([], true);

// definition is provided for parent class
        $this->assertEquals([
            'BaseModel',
            [
                'dep' => '@Inject:Dependency',
                'attr' => [],
                'age' => 18,
            ]
        ], $container->reflect('Model'));

        $this->assertEquals(['reflect:Model'], $container->getLogs());
    }

    public function testGetDefinition()
    {
        $container = new Container([], true);

        $this->assertEquals(
            [
                'dep' => '@Inject:Dependency',
                'attr' => [],
                'age' => 18,
            ],
            $container->getDefinition('Model')
        );

// check if BaseModel is also saved
        $this->assertEquals([
            'BaseModel' => [
                'dep' => '@Inject:Dependency',
                'attr' => [],
                'age' => 18,
            ],
            'Model' => [
                'dep' => '@Inject:Dependency',
                'attr' => [],
                'age' => 18,
            ]
        ], $container->getDefinitions());

        $this->assertEquals(['reflect:Model'], $container->getLogs());
    }

    public function testBuild()
    {
        $container = new Container([], true);

        $model = $container->build('Model', $container->getDefinition('Model'));

        $this->assertEquals(18, $model->getAge());

        $this->assertEquals([
            'reflect:Model',
            'build:Model',
            'reflect:Dependency',
            'build:Dependency'
        ], $container->getLogs());
    }

    public function testServices()
    {
        $container = new Container([
            'config' => function($c) {
                return new ArrayObject(['version' => 1]);
            },
            'configAlias' => 'config'
        ], true);

        $config = $container['configAlias'];

        $this->assertEquals(1, $config['version']);

        $this->assertEmpty($container->getLogs());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidService()
    {
        $container = new Container([
            'config' => new stdClass()
        ]);

        $container['config'];
    }

    public function testSetDefinition()
    {
        $container = new Container();

        $container->setDefinition('config', function($c) {
            return new ArrayObject(['version' => 1]);
        });

        $this->assertInstanceOf('Closure', $container->getDefinition('config'));
    }

    public function testGetNew()
    {
        $container = new Container();

        /** @var Model $model */
        $model = $container->getNew('Model', ['age' => 20]);

        $this->assertEquals(20, $model->getAge());
    }

    public function testSet()
    {
        $container = new Container();

        $container['test'] = new ArrayObject(['version' => 1]);

        $this->assertEquals(1, $container['test']['version']);

        $this->assertInstanceOf('ArrayObject', $container->getServices()['test']);
    }

    public function testInstance()
    {
        $container = new Container(['config' => function($c) {
            return new ArrayObject(['version' => 1]);
        }]);

        Container::setInstance($container);

// new container outside scope
        $container = new Container();

        $this->assertEquals(1, Container::instance()['config']['version']);
    }
}

class Dependency
{
    protected $value;

    public function __construct($value = 20)
    {
        $this->value = $value;
    }
}

class BaseModel
{
    protected $dep;
    protected $attr;
    protected $age;

    public function __construct(Dependency $dep, array $attr = [], $age = 18)
    {
        $this->dep = $dep;
        $this->attr = $attr;
        $this->age = $age;
    }

    public function getAge()
    {
        return $this->age;
    }
}

class Model extends BaseModel
{

}

