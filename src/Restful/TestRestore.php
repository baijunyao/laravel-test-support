<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestRestore
{
    public function testRestore()
    {
        $this->assertResponse(
            $this->patch(route($this->getRoute() . '.restore', $this->restoreId), [], $this->getHeaders())
        );
    }
}
