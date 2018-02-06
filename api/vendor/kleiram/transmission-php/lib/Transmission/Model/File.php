<?php
namespace Transmission\Model;

/**
 * @author Ramon Kleiss <ramon@cubilon.nl>
 */
class File extends AbstractModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $size;

    /**
     * @var integer
     */
    protected $completed;

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string) $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->size = (integer) $size;
    }

    /**
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param integer $size
     */
    public function setCompleted($completed)
    {
        $this->completed = (integer) $completed;
    }

    /**
     * @return integer
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @return boolean
     */
    public function isDone()
    {
        return $this->getSize() == $this->getCompleted();
    }

    /**
     * {@inheritDoc}
     */
    public static function getMapping()
    {
        return array(
            'name' => 'name',
            'length' => 'size',
            'bytesCompleted' => 'completed'
        );
    }
}
