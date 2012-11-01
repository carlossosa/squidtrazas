<?php

namespace BNJM\SquidTrazasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="BNJM\SquidTrazasBundle\Repository\QuotaConsumoRepository")
 * @ORM\Table(name="quotas_consumo")
 */
class QuotaConsumo {

    /**
     * @ORM\ManyToOne(targetEntity="SquidUsuario", cascade={"all"}, fetch="EAGER")
     * @ORM\JoinColumn(name="usuario_id", referencedColumnName="id")
     */
    protected $usuario;   
    
    /**
     * @ORM\Id
     * @ORM\Column(name="time",type="date")
     */
    private $time;
    
    /**
     * @ORM\Column(type="bigint")
     */
    protected $horas;
 
    /**
     * @ORM\Column(type="bigint")
     */
    protected $bytes;   

    /**
     * Set time
     *
     * @param \DateTime $time
     * @return QuotaConsumo
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;
    
        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set horas
     *
     * @param integer $horas
     * @return QuotaConsumo
     */
    public function setHoras($horas)
    {
        $this->horas = $horas;
    
        return $this;
    }

    /**
     * Get horas
     *
     * @return integer 
     */
    public function getHoras()
    {
        return $this->horas;
    }

    /**
     * Set bytes
     *
     * @param integer $bytes
     * @return QuotaConsumo
     */
    public function setBytes($bytes)
    {
        $this->bytes = $bytes;
    
        return $this;
    }

    /**
     * Get bytes
     *
     * @return integer 
     */
    public function getBytes()
    {
        return $this->bytes;
    }

    /**
     * Set usuario
     *
     * @param BNJM\SquidTrazasBundle\Entity\SquidUsuario $usuario
     * @return QuotaConsumo
     */
    public function setUsuario(\BNJM\SquidTrazasBundle\Entity\SquidUsuario $usuario = null)
    {
        $this->usuario = $usuario;
    
        return $this;
    }

    /**
     * Get usuario
     *
     * @return BNJM\SquidTrazasBundle\Entity\SquidUsuario 
     */
    public function getUsuario()
    {
        return $this->usuario;
    }
}