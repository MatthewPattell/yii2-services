<?php
/**
 * Created by PhpStorm.
 * User: konstantin
 * Date: 14.07.18
 * Time: 0:30
 */

namespace MP\Services\Stub\Models;

use MP\Services\Stub\ImplementServicesStub;
use MP\Services\Stub\Services\BaseControllerServiceStub;
use yii\base\Controller;
use yii\base\Module;

/**
 * Class ControllerStub
 *
 * @property-read BaseControllerServiceStub $service
 *
 * @package MP\Services\Stub
 */
class ControllerStub extends Controller
{
    use ImplementServicesStub;

    public $customField = 'foo';

    public static function createSelf(): self
    {
        return new self('stubController', new Module('stubModule'));
    }

    /**
     * @inheritdoc
     */
    public function services(): array
    {
        return [
            'service' => BaseControllerServiceStub::class,
        ];
    }
}