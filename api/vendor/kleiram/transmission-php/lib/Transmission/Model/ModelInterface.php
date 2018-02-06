<?php
namespace Transmission\Model;

/**
 * The interface Transmission models must implement
 *
 * @author Ramon Kleiss <ramon@cubilon.nl>
 */
interface ModelInterface
{
    /**
     * Get the mapping of the model
     *
     * @return array
     */
    public static function getMapping();
}
