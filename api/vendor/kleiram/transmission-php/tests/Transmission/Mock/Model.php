<?php
namespace Transmission\Mock;

use Transmission\Model\ModelInterface;

class Model implements ModelInterface
{
    private $fo;
    private $bar;
    private $unused;

    public function setFo($fo)
    {
        $this->fo = $fo;
    }

    public function getFo()
    {
        return $this->fo;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function setUnused($unused)
    {
        $this->unused = $unused;
    }

    public function getUnused()
    {
        return $this->unused;
    }

    public static function getMapping()
    {
        return array(
            'foo' => 'fo',
            'bar' => 'bar',
            'unused' => null
        );
    }
}
