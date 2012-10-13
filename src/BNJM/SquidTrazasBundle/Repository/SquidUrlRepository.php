<?php
namespace BNJM\SquidTrazasBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Carlos Sosa <carlitin at gmail dot com>
 */
class SquidUrlRepository extends EntityRepository {
    public function getListExclude ()
    {
        $exclude_list = array(  "http://192.168%",
                                "ftp://192.168%"
                            );
        
        $_Q = $this->createQueryBuilder('u')
                    ->select('u.id')
                ;
        for ($i=0;$i<count($exclude_list);$i++)
            if ( $i==0)
            {
                $_Q->where ("u.url LIKE :term".$i);
                $_Q->setParameter('term'.$i, $exclude_list[$i]);
            } else {
                $_Q->orWhere("u.url LIKE :term".$i);
                $_Q->setParameter('term'.$i, $exclude_list[$i]);
            }
            $return = array();
            
        foreach ( $_Q->getQuery()->setMaxResults(10)->getArrayResult() as $_D)
            $return[] = $_D['id'];
        
        return $return;
    }        
}