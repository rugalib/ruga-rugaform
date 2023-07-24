<?php

declare(strict_types=1);

namespace Ruga\Skeleton\Test;

use Laminas\ServiceManager\ServiceManager;

/**
 * @author                 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class SkeletonTest extends \Ruga\Skeleton\Test\PHPUnit\AbstractTestSetUp
{
    public function testCanSetContainer(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
