<?php

use MP\Services\Stub\Models\ControllerStub;
use PHPUnit\Framework\TestCase;

class IssetTests extends TestCase
{
    public function testServiceNotFound(): void
    {
        $controller = ControllerStub::createSelf();

        $this->assertFalse(isset($controller->wrongServiceName));
    }

    public function testServiceFound(): void
    {
        $controller = ControllerStub::createSelf();

        $this->assertTrue(isset($controller->service));
    }

    public function testNonServicePropertyFound(): void
    {
        $controller = ControllerStub::createSelf();

        $this->assertTrue(isset($controller->customField));
    }

    public function testNonServiceParentPropertyFound(): void
    {
        $controller = ControllerStub::createSelf();

        $this->assertTrue(isset($controller->uniqueId));
    }
}
