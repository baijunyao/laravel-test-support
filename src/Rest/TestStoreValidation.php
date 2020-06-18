<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestStoreValidation
{
    public function testStoreValidation()
    {
        $this->assertResponse(
            $this->postJson(route($this->getRoute() . '.store'))
        );
    }
}
