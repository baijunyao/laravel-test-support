<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestUpdate
{
    public function testUpdate()
    {
        $this->assertResponse(
            $this->putJson(route($this->getRoute() . '.update', $this->updateId), $this->updateData)
        );
    }
}
