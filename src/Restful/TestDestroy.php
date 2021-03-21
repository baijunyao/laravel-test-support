<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestDestroy
{
    public function testDestroy()
    {
        $this->assertResponse(
            $this->delete(route($this->getRoute() . '.destroy', $this->destroyId))
        );
    }
}
