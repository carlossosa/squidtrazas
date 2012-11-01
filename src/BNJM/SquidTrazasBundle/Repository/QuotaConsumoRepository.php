<?php
namespace BNJM\SquidTrazasBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Carlos Sosa <carlitin at gmail dot com>
 */
class QuotaConsumoRepository extends EntityRepository {
    
    public function getQuotaDataForUser (\BNJM\SquidTrazasBundle\Entity\SquidUsuario $squidusuario, $p_s, $p_e)
    {
        $q = $this->createQueryBuilder('q')
                    ->where('q.time >= :ps AND q.time <= :pe AND q.usuario = :user')                
                    ->setParameter('pe', $p_e)
                    ->setParameter('ps', $p_s)                    
                    ->setParameter('user', $squidusuario->getId())                    
                    ->select('SUM(q.bytes) AS bytes, SUM(q.horas) AS horas')
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
                    
        return $q;                    
    }
}