<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Ruga\Rugaform\DatasourcePlugins\DatasourcePluginManager;

/**
 * This factory creates a RugaformMiddleware. RugaformMiddleware is responsible for handling all the requests for
 * rugaform form processing.
 *
 * @see     RugaformMiddleware
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class RugaformMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareInterface
    {
        $middleware = new RugaformMiddleware($container->get(DatasourcePluginManager::class));
        return $middleware;
    }
}
