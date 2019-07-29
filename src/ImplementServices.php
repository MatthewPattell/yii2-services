<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 24.01.2018
 * Time: 12:10
 */

namespace MP\Services;

use yii\base\BaseObject;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;
use yii\base\UnknownPropertyException;

/**
 * Trait    ImplementServices
 * @package MP\Services
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 *
 * @mixin BaseObject
 */
trait ImplementServices
{
    /**
     * @var IService[]
     */
    private $servicesInstances = [];

    /**
     * Returns a list of services that this trait should attach to class.
     *
     * The return value of this method should be an array of configurations
     * indexed by services names. A service configuration can be either a
     * string specifying the service class or an array of the following
     * structure:
     *
     * ```php
     * 'serviceName1' => 'ServiceClass',
     * 'serviceName2' => [
     *     'class' => 'ServiceClass',
     *     'property1' => 'value1',
     *     'property2' => 'value2',
     * ],
     * ```
     *
     * Note that a service class must implement from [[IService]].
     *
     * Services declared in this method will be generated to the object
     * automatically (on demand).
     *
     * @return array the service configurations.
     */
    public function services(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     * @throws UnknownPropertyException
     * @throws InvalidConfigException
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $exception) {
            if (!$this->hasService($name)) {
                throw $exception;
            }

            return $this->getService($name);
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws UnknownPropertyException
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException|InvalidCallException $exception) {
            if (!$this->hasService($name)) {
                throw $exception;
            }

            $class = $this->getServiceConfig($name)['class'];
            if (!\is_object($value) || !($value instanceof $class)) {
                throw new InvalidCallException(sprintf('%s::%s expected to be instance of %s, but %s given', self::class, $name, $class, \is_object($value) ? \get_class($value) : \gettype($value)));
            }

            $this->servicesInstances[$name] = $value;
        }
    }

    /**
     * @inheritdoc
     * @param $name
     * @return bool
     * @throws InvalidConfigException
     */
    public function __isset($name)
    {
        return parent::__isset($name) || $this->hasService($name);
    }

    /**
     * @inheritdoc
     * @throws UnknownPropertyException
     * @throws InvalidConfigException
     */
    public function __unset($name)
    {
        try {
            parent::__unset($name);
        } catch (UnknownPropertyException|InvalidCallException $exception) {
            if (!$this->hasService($name)) {
                throw $exception;
            }

            unset($this->servicesInstances[$name]);
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function __call($name, $params)
    {
        try {
            return parent::__call($name, $params);
        } catch (UnknownMethodException $exception) {
            $sName = \lcfirst(substr($name, 3, \strlen($name)));
            $service = $this->getService($sName, (array) $params);

            if ($service === null) {
                throw $exception;
            }

            return $service;
        }
    }

    /**
     * Get service by name
     *
     * @param string $name
     * @param array $params
     *
     * @return IService|null
     * @throws InvalidConfigException
     */
    private function getService(string $name, array $params = []): ?IService
    {
        if (array_key_exists($name, $this->servicesInstances)) {
            return $this->servicesInstances[$name];
        }

        $serviceConfig = $this->getServiceConfig($name);
        if ($serviceConfig === null) {
            return null;
        }

        $class = $serviceConfig['class'];
        unset($serviceConfig['class']);
        $serviceConfig = array_merge($serviceConfig, isset($params[0]) && \is_array($params[0]) ? $params[0] : []);

        if (!class_exists($class)) {
            throw new InvalidConfigException(sprintf('Class %s does not exists', $class));
        }

        if (\is_subclass_of($class, BaseModelService::class)
            || \is_subclass_of($class, BaseControllerService::class)
        ) {
            $service = new $class($this, $serviceConfig);
        } else {
            $service = new $class($serviceConfig);
        }

        if (!($service instanceof IService)) {
            throw new InvalidConfigException('Service class must implement `' . IService::class . '`.');
        }

        return $this->servicesInstances[$name] = $service;
    }

    /**
     * Return all currently initialized services.
     * Visibility may be changed to access this list of services
     *
     * @return IService[]
     */
    private function getServicesInstances(): array
    {
        return $this->servicesInstances;
    }

    /**
     * @param string $name
     * @return bool
     * @throws InvalidConfigException
     */
    private function hasService(string $name): bool
    {
        return $this->getServiceConfig($name) !== null;
    }

    /**
     * Get service class name
     *
     * @param string $name
     *
     * @return array|string|NULL
     * @throws InvalidConfigException
     */
    private function getServiceConfig(string $name)
    {
        $config = $this->services()[$name] ?? null;
        if ($config === null) {
            return null;
        }

        if (\is_string($config)) {
            return [
                'class' => $config,
            ];
        }
        if (\is_array($config) && array_key_exists('class', $config)) {
            return $config;
        }

        throw new InvalidConfigException('Config must be either type of string (class name) or array (with `class` element');
    }
}
