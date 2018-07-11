<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 24.01.2018
 * Time: 12:10
 */

namespace MP\Services;

use yii\base\BaseObject;
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
     * indexed by services names. A service configuration can be either a string specifying
     * the service class or an array of the following structure:
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
     * Services declared in this method will be generated to the object automatically (on demand).
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
            $service = $this->getService($name);

            if ($service === NULL) {
                throw new UnknownPropertyException('', 0, $exception);
            }

            return $service;
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
            $sName = lcfirst(substr($name, 3, strlen($name)));
            $service = $this->getService($sName, (array) $params);

            if ($service === NULL) {
                throw new UnknownMethodException('', 0, $exception);
            }

            return $service;
        }
    }

    /**
     * Get service by name
     *
     * @param string $name
     * @param array  $params
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
        if ($serviceConfig === NULL) {
            return NULL;
        }

        $class  = NULL;
        $config = [];
        if (\is_string($serviceConfig)) {
            $class  = $serviceConfig;
            $config = [];
        } elseif (\is_array($serviceConfig)) {
            $class  = $serviceConfig['class'] ?? NULL;
            unset($serviceConfig['class']);
            $config = $serviceConfig;
        }
        $config = array_merge($config, isset($params[0]) && \is_array($params[0]) ? $params[0] : []);

        if ($class === NULL) {
            throw new InvalidConfigException('Object configuration must contain a "class" element.');
        }

        if (\is_subclass_of($class, BaseModelService::class)
            || \is_subclass_of($class, BaseControllerService::class)
        ) {
            $service = new $class($this, $config);
        } else {
            $service = new $class($config);
        }

        if (!($service instanceof IService)) {
            throw new InvalidConfigException('Service class must implement `' . IService::class . '`.');
        }

        return $this->servicesInstances[$name] = $service;
    }

    /**
     * Get service class name
     *
     * @param string $name
     *
     * @return array|string|NULL
     */
    private function getServiceConfig(string $name)
    {
        return $this->services()[$name] ?? NULL;
    }
}
