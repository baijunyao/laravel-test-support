<?php

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestForceDelete
{
    public function testForceDelete()
    {
        $this->assertResponse(
            $this->delete(route($this->getRoute() . '.forceDelete', $this->forceDeleteId))
        );
    }
}
