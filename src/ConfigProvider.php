<?php

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * ConfigProvider.
 *
 * @see    https://docs.mezzio.dev/mezzio/v3/features/container/config/
 */
class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => [
                'services' => [],
                'aliases' => [],
                'factories' => [],
                'invokables' => [],
                'delegators' => [],
            ],
        ];
    }
}
