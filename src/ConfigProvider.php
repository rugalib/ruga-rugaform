<?php

declare(strict_types=1);

namespace Ruga\Rugaform;

use Ruga\Rugaform\DatasourcePlugins\DatasourcePluginManagerFactory;
use Ruga\Rugaform\DatasourcePlugins\DatasourcePluginManager;
use Ruga\Rugaform\DatasourcePlugins\Model;
use Ruga\Rugaform\DatasourcePlugins\ModelFactory;
use Ruga\Rugaform\Middleware\RugaformMiddleware;
use Ruga\Rugaform\Middleware\RugaformMiddlewareFactory;

/**
 * ConfigProvider.
 *
 * @author Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class ConfigProvider
{
    public function __invoke()
    {
        return [
            'ruga' => [
                'asset' => [
                    'rugalib/ruga-rugaform' => [
                        'scripts' => ['jquery.rugaform.js'],
                        'stylesheets' => ['jquery.rugaform.css'],
                    ],
                ],
            ],
            'dependencies' => [
                'services' => [],
                'aliases' => [],
                'factories' => [
                    RugaformMiddleware::class => RugaformMiddlewareFactory::class,
                    DatasourcePluginManager::class => DatasourcePluginManagerFactory::class,
                ],
                'invokables' => [],
                'delegators' => [],
            ],
            Rugaform::class => [
                'datasourcePlugins' => [
                    'aliases' => [
                        'model' => Model::class,
                    ],
                    'factories' => [
                        Model::class => ModelFactory::class,
                    ],
                ],
            ],
        
        ];
    }
}
