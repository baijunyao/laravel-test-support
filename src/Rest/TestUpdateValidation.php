<?php

declare(strict_types=1);

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
