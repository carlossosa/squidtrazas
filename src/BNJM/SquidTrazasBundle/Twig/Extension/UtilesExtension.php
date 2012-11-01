<?php
namespace BNJM\SquidTrazasBundle\Twig\Extension;

class UtilesExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'utiles';
    }
    
    public function getFilters()
    {
        return array ( 
                        'humanreadable' => new \Twig_Filter_Method( $this, 'humanreadable'),
                        'httpcode' => new \Twig_Filter_Method( $this, 'httpcode'),
                        'humantime' => new \Twig_Filter_Method( $this, 'humantime'),
                        'humanizethetime' => new \Twig_Filter_Method( $this, 'humanizethetime'),
                        );
    }
    
    public function humanreadable( $bytes)
    {
        $KB = 1024;
        $MB = 1024*1024;
        $GB = 1024*1024*1024;
        $TB = 1024*1024*1024*1024; //LO DUDO
        $bytes = floatval($bytes);
        
        if ( $bytes > $TB)
        {
            return round( $bytes/$TB, 2)." TB";
        } else if ( $bytes > $GB) {
            return round( $bytes/$GB, 2)." GB";
        } else if ( $bytes > $MB) {
            return round( $bytes/$MB, 2)." MB";
        } else if ( $bytes > $KB) {
            return round( $bytes/$KB, 2)." KB";
        } else {
            return $bytes." B";
        }        
    }
    
    public function httpcode( $string)
    {
        
        $arr = '#^\w+_(\w+)([_\w]|)/(\d+)#';
        $r= array();
        preg_match($arr, $string, $r);                
        return ( is_array($r) && count($r) > 0 ) ? $r[1]. "(". $r[3] . ")" : $string;        
    }
    
    public function humantime( $miliseconds)
    {                
        return round($miliseconds/1000,2)."s";
    }
    
    public function humanizethetime ( $miliseconds)
    {
        $string = ''; $horas = $minutos = 0; $seconds = intval($miliseconds/1000);
        $miliseconds = $miliseconds % 1000;
        if ( $seconds > 3599)
        {
            $horas = intval($seconds / 3600);
            $seconds = $seconds % 3600;
        }
        if ( $seconds > 59)
        {
            $minutos = intval($seconds / 60);
            $seconds = $seconds % 60;
        }    
        return "{$horas}H {$minutos}M {$seconds}.{$miliseconds}S";
    }
}