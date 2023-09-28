<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Rugaform\Test;

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
