<?php

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestDestroy
{
    public function testDestroy()
    {
        $this->assertResponse(
            $this->delete(route($this->getRoute() . '.destroy', $this->destroyId))
        );
    }
}
