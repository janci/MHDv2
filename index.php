<?php 
// library has support only with UTF8 encode names
$starttime = microtime();
$startarray = explode(" ", $starttime);
$starttime = $startarray[1] + $startarray[0];



require_once('libs/mhd.php');									//load library
$mhd = new MHD();										//create an instance of class MHD
$mhd->setExpireTime(2);									//7*24*60 - default
$mhd->setCacheType('FileCache');								//set type for caching
$zastavky = $mhd->getStops('25');								//get Stops for Bus 10
$pole = $mhd->getDepartures('25','Moldavská, obchodné centrá');							//get departures for bus 10 on stop "Staničné námestie"
var_dump($pole);

$endtime = microtime();
$endarray = explode(" ", $endtime);
$endtime = $endarray[1] + $endarray[0];
$totaltime = $endtime - $starttime; 
$totaltime = round($totaltime,5);
echo "<br /><br /><center><font color='red'><small>This page loaded in $totaltime seconds.</small></font></center>";



