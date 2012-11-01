<?php
namespace BNJM\SquidTrazasBundle\Doctrine\Query\Mysql;

class GroupBy extends \Doctrine\ORM\Query\Expr\Base
{
    /**
     * @var string
     */
    protected $preSeparator = '';

    /**
     * @var string
     */
    protected $separator = ', ';

    /**
     * @var string
     */
    protected $postSeparator = '';

    /**
     * @var array
     */
    protected $allowedClasses = array();

    /**
     * @var array
     */
    protected $parts = array();

    /**
     * @param string $sort
     * @param string $order
     */
    public function __construct($sort = null, $order = null)
    {
        if ($sort) {
            $this->add($sort, $order);
        }
    }

    /**
     * @param string $sort
     * @param string $order
     */
    public function add($sort, $order = null)
    {
        $order = ! $order ? 'ASC' : $order;
        $this->parts[] = $sort . ' '. $order;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @return string
     */
    public function __tostring()
    {
        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}