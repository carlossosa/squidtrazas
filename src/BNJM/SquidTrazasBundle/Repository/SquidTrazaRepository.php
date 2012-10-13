<?php
namespace BNJM\SquidTrazasBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Carlos Sosa <carlitin at gmail dot com>
 */
class SquidTrazaRepository extends EntityRepository {
    
    public function numRecords(\Doctrine\ORM\QueryBuilder $query)
    {
        return $query->select('COUNT(t) AS num')
                        ->resetDQLPart('orderBy')
                        ->getQuery()
                        ->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache())
                        ->useResultCache(true, 60, 'numRecords')
                        ->getSingleResult();
    }
    
    public function sumSizeRecords(\Doctrine\ORM\QueryBuilder $query)
    {
        $exp = $query->expr();
        return $query->select('SUM(t.size) AS totalsize')
                       //TOO SLOW NEED RESOLV 
                        //->andWhere($exp->notIn('t.domain', $this->getEntityManager()->getRepository('SquidTrazasBundle:SquidDomain')->getListExclude()))
                        ->resetDQLPart('orderBy')
                        ->getQuery()
                        ->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache())
                        ->setQueryCacheLifetime(600)          
                        ->setResultCacheLifetime(600)
                        ->useResultCache(true,600,'sumSize')
                        ->getSingleResult();
    }
    
    public function getResultOfPage ($page, $num_per_page = 100, \Doctrine\ORM\QueryBuilder $query) 
    {
        //calculo simple de la cantidad paginas
        $_total = $this->numRecords(clone $query);
        $total_registros = $_total['num'];
        $paginas = intval($total_registros / $num_per_page);
        $page_start_records = ( $page <= $paginas && $page > 0 ) ? intval(($page-1)*$num_per_page) : 0;
        
        //total size
        $_size = $this->sumSizeRecords(clone $query);
        $size = $_size['totalsize'];
        
        return array( 'trazas' => $this->getResults( $page_start_records, $num_per_page, $query), 
                                                     'page' => array('first' => 1,
                                                                      'last' => $paginas,
                                                                      'next' => ( $page < $paginas && $page > 0 ) ? $page+1 : $paginas,
                                                                      'prev' => ( $page > 1 && $page <= $paginas) ? $page-1 : 1,
                                                                      'page' => $page,
                                                                      'num'  => $num_per_page
                                                                                    ), 
                                                     'records' => $_total,
                                                     'total_size' => $size);
    }
    
    public function getResults($start = 0, $limit = 100, \Doctrine\ORM\QueryBuilder $query) {
       return $query->getQuery()
                    ->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache())
                    ->useResultCache( true, 60)
                    ->setFirstResult($start)
                    ->setMaxResults($limit)                    
                    ->getResult();                 
    }
    
    public function getAll( $page, $num_per_page) {
       $query = $this->createQueryBuilder('t')
                        ->select('t')
                        ->orderBy('t.id', 'DESC');
       return $this->getResultOfPage($page, $num_per_page, $query);                                        
    }  
    
    public function getAllInPeriod( $page, $num_per_page, $time_start, $time_end) {
       $query = $this->createQueryBuilder('t')
                        ->select('t')               
                        ->orderBy('t.id', 'DESC');
       
       if ( $time_start != null)
       {
           $query->andWhere('t.time >= :timestart')
                   ->setParameter('timestart', $time_start);
       }
       
       if ( $time_end != null)
       {
           $query->andWhere('t.time <= :timeend')
                   ->setParameter('timeend', $time_end);
       }
       
       //TODO USE ENTRE
       
       return $this->getResultOfPage($page, $num_per_page, $query);                                        
    }
    
    public function getTrazaUsuario ( $page, $num_per_page, $username, $ip, $time_start, $time_end) {
        $query = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from($this->_entityName, 't')
                        ->innerJoin('t.usuario', 'u')
                        ->select('t, u')
                        ->where('u.username = :username')
                        ->setParameter('username', $username)
                        ->orderBy('t.id', 'DESC');
        if ( $ip != NULL)
            $query->innerJoin ('t.ip', 'i')
                    ->andWhere ('i.ip = :ip')
                    ->setParameter ('ip', $ip);
        
       if ( $time_start != null)
       {
           $query->andWhere('t.time >= :timestart')
                   ->setParameter('timestart', $time_start);
       }
       
       if ( $time_end != null)
       {
           $query->andWhere('t.time <= :timeend')
                   ->setParameter('timeend', $time_end);
       }
        
       return $this->getResultOfPage($page, $num_per_page, $query);        
    }
    
    public function getTrazaIP ( $page, $num_per_page, $ip, $time_start, $time_end) {
        $query = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from($this->_entityName, 't')
                        ->innerJoin('t.ip', 'i')
                        ->select('t, i')
                        ->where('i.ip = :ip')
                        ->setParameter('ip', $ip)
                        ->orderBy('t.id', 'DESC');
       
       if ( $time_start != null)
       {
           $query->andWhere('t.time >= :timestart')
                   ->setParameter('timestart', $time_start);
       }
       
       if ( $time_end != null)
       {
           $query->andWhere('t.time <= :timeend')
                   ->setParameter('timeend', $time_end);
       }
        
       return $this->getResultOfPage($page, $num_per_page, $query);        
    }
    
    public function getTrazaRangoIP ( $page, $num_per_page, $ip_upper, $ip_lower, $time_start, $time_end) {
        $query = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from($this->_entityName, 't')
                        ->innerJoin('t.ip', 'i')
                        ->select('t, i')
                        ->where('i.ip <= :upper')
                        ->andWhere('i.ip >= :lower')
                        ->setParameter('upper', $ip_upper)
                        ->setParameter('lower', $ip_lower)
                        ->orderBy('t.id', 'DESC');
       
       if ( $time_start != null)
       {
           $query->andWhere('t.time >= :timestart')
                   ->setParameter('timestart', $time_start);
       }
       
       if ( $time_end != null)
       {
           $query->andWhere('t.time <= :timeend')
                   ->setParameter('timeend', $time_end);
       }
        
       return $this->getResultOfPage($page, $num_per_page, $query);        
    }
    
    /**
     *TOP USER
     */
      
    public function topUserExtendQuery( $tipo, $period_start = null, $period_end = null, $limit = 20, $cachettl = 300, $order = 'DESC')
    {
        $_Q = $this->getEntityManager()        
                ->createQueryBuilder()                
                ->from($this->_entityName, 't')
                ->select('u.username nombre')
                ->addSelect('COUNT(t.url) accesos')
                ->addSelect('SUM(t.size) bw')
                ->addSelect('SUM(t.transferTime) tiempo')
                ->innerJoin('t.usuario', 'u');
        
        if ( $period_start != null && $period_end == null)
                $_Q->where($_Q->expr ()->gte ('t.time', ":periodstart"));
        if ( $period_start == null && $period_end != null)
                $_Q->where($_Q->expr ()->lte ('t.time', ":periodend"));
        if ( $period_start != null && $period_end != null)
                $_Q->where($_Q->expr ()->between('t.time', ':periodstart', ':periodend'));
        if ( $period_start != null )
            $_Q->setParameter('periodstart', $period_start);
        if ( $period_end != null )
            $_Q->setParameter('periodend', $period_end);
        
        return $_Q->groupBy('t.usuario')
                    ->orderBy($tipo, $order)
                    ->getQuery()
                    ->setMaxResults($limit)
                    ->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache())
                    ->setResultCacheLifetime($cachettl)
                    ->useResultCache(true)
                    ->getArrayResult();
    }
 
    public function topActiveUsers ( $p_s, $p_e)
    {        
        return $this->topUserExtendQuery('accesos', $p_s, $p_e);
    }
    
    public function topBwUsers ($p_s, $p_e)
    {        
        return $this->topUserExtendQuery('bw', $p_s, $p_e);
    }
    
    public function topTimeUsers ($p_s, $p_e)
    {        
        return $this->topUserExtendQuery('tiempo', $p_s, $p_e);
    }
    
    public function topUser( $period_start = null, $period_end = null)
    {
            return array('activos' =>  $this->topActiveUsers($period_start,$period_end),
                         'trafico' =>  $this->topBwUsers($period_start,$period_end),
                         'tiempo' =>  $this->topTimeUsers($period_start,$period_end)
                        );
    }
    
    public function topBwUserMonth( $month)
    {
        
    }   
    
    public function topDomainEver () 
    {
        return $this->getEntityManager()        
                ->createQueryBuilder()                
                ->from($this->_entityName, 't')
                ->select('d.domain dominio')
                ->addSelect('COUNT(t.url) accesos')
                ->addSelect('SUM(t.size) bw')
                ->addSelect('SUM(t.transferTime) tiempo')
                ->innerJoin('t.domain', 'd')
                ->groupBy('t.domain')
                ->orderBy('bw', 'DESC')
                ->getQuery()
                ->setMaxResults(50)
                ->setResultCacheDriver(new \Doctrine\Common\Cache\ApcCache())
                ->setResultCacheLifetime(3600)
                ->useResultCache(true)
                ->getArrayResult();
    }  
    
    public function topDomainEverForUser ( $username)
    {
        return $this->getEntityManager()        
                ->createQueryBuilder()                
                ->from($this->_entityName, 't')
                ->select('d.domain dominio')
                ->addSelect('COUNT(t.url) accesos')
                ->addSelect('SUM(t.size) bw')
                ->addSelect('SUM(t.transferTime) tiempo')
                ->innerJoin('t.domain', 'd')
                ->groupBy('t.domain')
                ->orderBy('bw', 'DESC')
                ->where('t.usuario = :user')
                ->setParameter('user', $this->getEntityManager()
                                            ->getRepository('SquidTrazasBundle:SquidUsuario')
                                            ->getUserByUsername($username)
                                            ->getId())
                ->getQuery()
                ->setMaxResults(50)
                ->setResultCacheDriver(new \Doctrine\Common\Cache\ArrayCache())
                ->setResultCacheLifetime(3600)
                ->useResultCache(true)
                ->getArrayResult();
    } 
    
    public function getIpOfUser ( $username)
    {
        $ips = $this->createQueryBuilder('t')
                        ->select('t')
                        ->groupBy('t.ip')
                        ->innerJoin('t.ip', 'i')
                        ->where('t.usuario = :user')
                        ->orderBy('t.time', 'DESC')
                        ->setParameter('user', $this->getEntityManager()
                                            ->getRepository('SquidTrazasBundle:SquidUsuario')
                                            ->getUserByUsername($username)
                                            ->getId())
                        ->getQuery()
                        ->getResult();      
        
                foreach ( $ips as $ip)          
                {
                    $t = $this->getLastAccess( $ip->getUsuario(), $ip->getIp()->getId());
                    $ip_list[] = array( 'ip' => $ip->getIp()->__toString(),
                                        'time' => $t[0]['time']);
                }
       return $ip_list;
    }
    
    public function getLastAccess ( $username, $ip)
    {
        return $this->createQueryBuilder('t')
                        ->select('t.time time')
                        ->where('t.ip = :ip AND t.usuario = :user')
                        ->setParameter('ip', $ip)
                        ->setParameter('user', $username)
                        ->orderBy('t.time', 'DESC')
                        ->getQuery()
                        ->setMaxResults(1)
                        ->getResult();
    }
}