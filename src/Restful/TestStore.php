<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestStore
{
    public function testStore()
    {
        $this->assertResponse(
            $this->postJson(route($this->getRoute() . '.store'), $this->storeData, $this->getHeaders())
        );
    }
}
