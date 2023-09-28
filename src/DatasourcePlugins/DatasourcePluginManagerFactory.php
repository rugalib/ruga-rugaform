<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\DatasourcePlugins;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Ruga\Rugaform\DatasourcePlugins\DatasourcePluginManager;
use Ruga\Rugaform\Rugaform;

/**
 * @see     DatasourcePluginManager
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class DatasourcePluginManagerFactory
{
    public function __invoke(ContainerInterface $container): DatasourcePluginManager
    {
        $config = ($container->get('config') ?? [])[Rugaform::class]['datasourcePlugins'] ?? [];
        return new DatasourcePluginManager($container, $config);
    }
}
