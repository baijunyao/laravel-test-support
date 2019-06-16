<?php

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestStore
{
    public function testStore()
    {
        $this->assertResponse(
            $this->postJson(route($this->getRoute() . '.store'), $this->storeData)
        );
    }
}
