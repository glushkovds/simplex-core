<?php

namespace Simplex\Core\Routing;

class Route
{
    /** @var string */
    protected $componentClassName;
    /** @var string|null */
    protected $componentAction;

    /**
     * @param string $componentClassName
     * @param string|null $componentAction
     */
    public function __construct(string $componentClassName, ?string $componentAction = null)
    {
        $this->componentClassName = $componentClassName;
        $this->componentAction = $componentAction;
    }

    /**
     * @return string
     */
    public function getComponentClassName(): string
    {
        return $this->componentClassName;
    }

    /**
     * @return string|null
     */
    public function getComponentAction(): ?string
    {
        return $this->componentAction;
    }


}