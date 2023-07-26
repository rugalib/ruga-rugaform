<?php

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
