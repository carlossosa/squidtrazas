<?php

namespace BNJM\SquidTrazasBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="BNJM\SquidTrazasBundle\Repository\QuotaUsuarioRepository")
 * @ORM\Table(name="quotas_user")
 */
class QuotaUsuario {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;   
    
    /**
     * @ORM\Column(type="bigint")
     */
    protected $quotaHoras;
 
    /**
     * @ORM\Column(type="bigint")
     */
    protected $quotaMegas;   

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quotaHoras
     *
     * @param integer $quotaHoras
     * @return QuotaUsuario
     */
    public function setQuotaHoras($quotaHoras)
    {
        $this->quotaHoras = $quotaHoras;
    
        return $this;
    }

    /**
     * Get quotaHoras
     *
     * @return integer 
     */
    public function getQuotaHoras()
    {
        return $this->quotaHoras;
    }

    /**
     * Set quotaMegas
     *
     * @param integer $quotaMegas
     * @return QuotaUsuario
     */
    public function setQuotaMegas($quotaMegas)
    {
        $this->quotaMegas = $quotaMegas;
    
        return $this;
    }

    /**
     * Get quotaMegas
     *
     * @return integer 
     */
    public function getQuotaMegas()
    {
        return $this->quotaMegas;
    }
}