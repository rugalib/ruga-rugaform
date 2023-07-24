<?php

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * Abstract template.
 */
abstract class AbstractVorlage implements VorlageInterface, NameableVorlageInterface
{
    use NameableVorlageTrait;
    
    /**
     * Return the name of this template.
     *
     * @return string
     */
    public function myName(): string
    {
        return $this->getName();
    }
}
