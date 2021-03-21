<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestUpdateValidation
{
    public function testUpdateValidation()
    {
        $this->assertResponse(
            $this->putJson(route($this->getRoute() . '.update', $this->updateId))
        );
    }
}
