<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Restful;

trait TestIndex
{
    public function testIndex()
    {
        $this->assertResponse(
            $this->getJson(route($this->getRoute() . '.index') . '?' . http_build_query($this->parameter), $this->getHeaders())
        );
    }
}
