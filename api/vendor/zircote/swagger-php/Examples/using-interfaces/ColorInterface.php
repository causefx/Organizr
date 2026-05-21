<?php

namespace UsingInterfaces;

/**
 * Not a schema.
 */
interface ColorInterface
{

    /**
     * The product color.
     *
     * @OA\Property(property="color", example="blue")
     */
    public function getColor();
}
