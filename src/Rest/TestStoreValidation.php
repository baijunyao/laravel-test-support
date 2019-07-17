<?php

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
