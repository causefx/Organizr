<?php

namespace UsingInterfaces;

/**
 * @OA\Schema(title="Pet")
 */
class Pet implements ColorInterface
{

    /**
     * {@inheritDoc}
     */
    public function getColor()
    {
        return "green";
    }
}
