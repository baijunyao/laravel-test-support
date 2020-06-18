<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestShow
{
    public function testShow()
    {
        $this->assertResponse(
            $this->get(route($this->getRoute() . '.show', $this->showId))
        );
    }
}
