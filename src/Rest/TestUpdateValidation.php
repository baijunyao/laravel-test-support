<?php

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestUpdateValidation
{
    public function testUpdateValidation()
    {
        $this->assertResponse(
            $this->putJson(route($this->getRoute() . '.update', $this->updateId))
        );
    }
}
