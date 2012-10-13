<?php
class ManageDate{
  private $timestamp = '';
  public $number_week = '';
  public $day = '';
  public $month	= '';
  public $year = '';
  public $format = 'Y-m-d';
  private $dias = array('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo');
  private $meses= array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

  public function __construct(){
	  if(empty($this->year))
		  $this->year = date('Y');
	  if(empty($this->number_week))
		  $this->number_week = (date('W'))-1;
	  if(empty($this->month))
		  $this->month = date('m');
    if(empty($this->day))
      $this->day = date('d');
  }

  public function DayStartWeek(){
	  $this->timestamp  = strtotime('+' . $this->number_week . ' weeks', strtotime($this->year . '0101'));
	  $date_m = strtotime('-' . date('w', $this->timestamp) + 1 . ' days', $this->timestamp);
	  return date($this->format, $date_m);
  }

  public function DayEndWeek(){
	  return date($this->format, strtotime('+6 days', strtotime( $this->DayStartWeek() )));
  }

  public function LastDayMonth(){
	  $last =  strftime("%d", mktime(0, 0, 0, $this->month+1, 0, $this->year));
	  $lastday = $last.'-'.$this->month.'-'.$this->year;
	  return date($this->format, strtotime($lastday));
  }

  public function RestarDias($num, $day_n){
	  return date($this->format, strtotime("-{$num} days", strtotime( $day_n )));
  }

  public function SumarDias($num, $day_n){
	  return date($this->format, strtotime("+{$num} days", strtotime( $day_n )));
  }

 public function getDiaWeek($day_n=''){
    $val = ($day_n=='') ? date($this->format) : $day_n;
    $bool = date('N',strtotime($val));
    return $this->dias[$bool-1];
 }
 
 public function getMes($day_n=''){
   $val = ($day_n=='') ? date($this->format) : $day_n;
   $bool = date('n',strtotime($val));
   return $this->meses[$bool-1];
 }
 


 /*        function next_week($target_week_day){
         $date = getDate();

         $day = $date["mday"];
         $week_day = $date["wday"];
         $month = $date["mon"];
         $year = $date["year"];

         //This assumes that if today is a target week day,
         //today's date will be used and not next week's.
         //To change that, just make
         //if($week_day <= $target_week_day)
         //if($week_day < $target_week_day)
         if($week_day < $target_week_day)
            $days_left = $target_week_day - $week_day;
         else
            $days_left = 7 - ($week_day - $target_week_day);

         //This script works by finding out the number of days separating
         //the current date and the next target week day.
         $next_week = getDate(mktime(0, 0, 0, $month, $day + $days_left, $year));

         $next_week_html = $next_week["month"] . " " . $next_week["mday"] . " , " . $next_week["year"];

         return($next_week_html);
        }
        $dw=next_week(3);
        echo "$dw";

    }*/

}
?>