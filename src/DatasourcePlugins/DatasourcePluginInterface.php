<?php

declare(strict_types=1);

namespace Ruga\Rugaform\DatasourcePlugins;

use Ruga\Rugaform\Middleware\RugaformRequest;
use Ruga\Rugaform\Middleware\RugaformResponse;

interface DatasourcePluginInterface
{
    /**
     * Handle the request from the form and return the response.
     *
     * @param RugaformRequest $rugaformRequest
     *
     * @return RugaformResponse
     */
    public function process(RugaformRequest $rugaformRequest): RugaformResponse;
}
