<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestUpdate
{
    public function testUpdate()
    {
        $this->assertResponse(
            $this->putJson(route($this->getRoute() . '.update', $this->updateId), $this->updateData)
        );
    }
}
