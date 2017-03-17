<?php

namespace Rapture\Container;

class Container implements \ArrayAccess
{
    const INJECT = '@Inject:';

    /** @var array */
    protected $definitions = [];

    /** @var array */
    protected $services = [];

    /** @var array */
    protected $logs = [];

    /** @var bool */
    protected $isLogOn = false;

    /** @var Container */
    protected static $instance;

    /**
     * Container constructor.
     *
     * @param array $definitions
     * @param bool $isLogOn
     */
    public function __construct(array $definitions = [], $isLogOn = false)
    {
        $this->definitions = $definitions;
        $this->isLogOn     = (bool)$isLogOn;
    }

    /**
     * instance
     *
     * @param string $namespace
     *
     * @return self
     */
    public static function instance(string $namespace = 'default'):Container
    {
        return self::$instance[$namespace];
    }

    /**
     * setInstance
     *
     * @param Container $instance
     * @param string $namespace
     */
    public static function setInstance(Container $instance, string $namespace = 'default')
    {
        self::$instance[$namespace] = $instance;
    }

    /**
     * setDefinition
     *
     * @param string $name
     * @param mixed $definition
     *
     * @return $this
     */
    public function setDefinition(string $name, $definition)
    {
        $this->definitions[$name] = $definition;

        return $this;
    }

    /**
     * getDefinition
     *
     * @param string $className
     *
     * @return mixed
     */
    public function getDefinition(string $className)
    {
        if (!isset($this->definitions[$className])) {
            list($parentName, $definition)  = $this->reflect($className);
            $this->definitions[$parentName] = $definition;
            $this->definitions[$className]  = $definition;
        }

        return $this->definitions[$className];
    }

    /**
     * getNew
     *
     * @param string $className
     * @param array $params
     *
     * @return \stdClass
     */
    public function getNew(string $className, array $params = [])
    {
        return $this->build($className, array_replace($this->getDefinition($className), $params));
    }

    /**
     * build
     *
     * @param $className
     * @param array $definition
     *
     * @return \stdClass
     */
    public function build(string $className, array $definition = [])
    {
        $this->log(__FUNCTION__, $className);

        $params = [];

        foreach ($definition as $param) {
            $params[] = (is_string($param) && substr($param, 0, strlen(self::INJECT)) === self::INJECT)
                ? $this[substr($param, strlen(self::INJECT))]
                : $param;
        }

        // avoid call_user_func for speed improvements
        $params += [null, null, null, null, null, null, null, null, null, null];

        return new $className(
            $params[0],
            $params[1],
            $params[2],
            $params[3],
            $params[4],
            $params[5],
            $params[6],
            $params[7],
            $params[8],
            $params[9]
        );
    }

    /**
     * reflect
     *
     * @param $className
     *
     * @return array
     */
    public function reflect(string $className)
    {
        if (!method_exists($className, '__construct')) {
            return [$className, []];
        }

        $this->log(__FUNCTION__, $className);

        $method = new \ReflectionMethod($className, '__construct');
        $parent = $method->class; // where the constructor was defined

        if (isset($this->definitions[$parent])) {
            return [$parent, $this->definitions[$parent]];
        }

        $params = $method->getParameters();

        // $matches[2] holds the type-hint
        preg_match_all('#\<(optional|required)\> ([^ $]*)#i', (string)$method, $matches);

        $definition = [];

        foreach ($matches[2] as $i => $typeHint) {
            $definition[$params[$i]->name] = (isset($typeHint[0]) && $typeHint !== 'array')
                ? self::INJECT . $typeHint
                : ($params[$i]->isDefaultValueAvailable() ? $params[$i]->getDefaultValue() : null);
        }

        return [$parent, $definition];
    }

    /**
     * getDefinitions
     *
     * @return array
     */
    public function getDefinitions():array
    {
        return $this->definitions;
    }

    /**
     * getServices
     *
     * @return array
     */
    public function getServices():array
    {
        return $this->services;
    }

    /**
     * getLogs
     *
     * @return array
     */
    public function getLogs():array
    {
        return $this->logs;
    }

    /**
     * log
     *
     * @param $method
     * @param $className
     *
     * @return void
     */
    protected function log($method, $className)
    {
        if ($this->isLogOn) {
            $this->logs[] = "{$method}:{$className}";
        }
    }

    /*
     * ArrayAccess
     */

    /**
     * offsetExists
     *
     * @param mixed $service
     *
     * @return bool
     */
    public function offsetExists($service)
    {
        return isset($this->services[$service]);
    }

    /**
     * offsetGet
     *
     * @param mixed $service
     *
     * @return object|null
     */
    public function offsetGet($service)
    {
        if (!isset($this->services[$service])) {
            if (!isset($this->definitions[$service])) {
                $this->getDefinition($service);
            }

            if ($this->definitions[$service] instanceof \Closure) {
                $this->services[$service] = $this->definitions[$service]($this);
            }
            elseif (is_array($this->definitions[$service])) {
                $this->services[$service] = $this->build($service, $this->definitions[$service]);
            }
            elseif (is_string($this->definitions[$service])) {
                return $this[$this->definitions[$service]];
            }
            else {
                throw new \InvalidArgumentException("Invalid service declaration: {$service}");
            }
        }

        return $this->services[$service];
    }

    /**
     * offsetSet
     *
     * @param mixed $name
     * @param mixed $service
     *
     * @return void
     */
    public function offsetSet($name, $service)
    {
        $this->services[$name] = $service;
    }

    /**
     * offsetUnset
     *
     * @param mixed $name
     *
     * @return void
     */
    public function offsetUnset($name)
    {
        unset($this->services[$name]);
    }
}
