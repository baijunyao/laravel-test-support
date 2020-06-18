<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestRestore
{
    public function testRestore()
    {
        $this->assertResponse(
            $this->patch(route($this->getRoute() . '.restore', $this->restoreId))
        );
    }
}
