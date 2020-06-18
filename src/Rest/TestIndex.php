<?php

declare(strict_types=1);

namespace Baijunyao\LaravelTestSupport\Rest;

trait TestIndex
{
    public function testIndex()
    {
        $this->assertResponse(
            $this->get(route($this->getRoute() . '.index'))
        );
    }
}
