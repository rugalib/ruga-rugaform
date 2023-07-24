<?php

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * Interface to a template.
 */
interface VorlageInterface
{
    /**
     * Return the name of this template.
     *
     * @return string
     */
    public function myName(): string;
}
