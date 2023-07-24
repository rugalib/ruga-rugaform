<?php

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * Provides functions for a nameable template.
 */
trait NameableVorlageTrait
{
    /**
     * The name.
     *
     * @var string
     */
    private $name = "";
    
    
    
    /**
     * Return the name of this template.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    
    
    /**
     * Set the name of this template.
     *
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
