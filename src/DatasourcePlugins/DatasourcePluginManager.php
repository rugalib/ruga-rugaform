<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\DatasourcePlugins;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * The DatasourcePluginManager loads plugin classes based on the first component of the form POST uri.
 * The plugin then handles the form request and returns a DatasourceResponse.
 *
 * @author Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class DatasourcePluginManager extends AbstractPluginManager
{
    /**
     * An object type that the created instance must be instanced of
     *
     * @var null|string
     */
    protected $instanceOf = DatasourcePluginInterface::class;
    
}
