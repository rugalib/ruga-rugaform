<?php

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * Interface to a nameable template.
 */
interface NameableVorlageInterface
{
    /**
     * Return the name of this template.
     *
     * @return string
     */
    public function getName(): string;
    
    
    
    /**
     * Set the name of this template.
     *
     * @param string $name
     */
    public function setName(string $name);
}
