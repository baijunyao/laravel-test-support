<?php

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
