<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\DatasourcePlugins;

use Psr\Container\ContainerInterface;
use Ruga\Db\Adapter\Adapter;

/**
 * @see     Model
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class ModelFactory
{
    public function __invoke(ContainerInterface $container): DatasourcePluginInterface
    {
        return new Model($container->get(Adapter::class));
    }
}
