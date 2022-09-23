<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestShow
{
    public function testShow()
    {
        $this->assertResponse(
            $this->get(route($this->getRoute() . '.show', $this->showId) . '?' . http_build_query($this->parameter), $this->getHeaders())
        );
    }
}
