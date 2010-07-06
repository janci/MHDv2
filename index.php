<?php 
/**
 * MHD PHP Class.
 *
 * This source file is a class for generating and getting information for connexion
 * in City Kosice, Slovakia.
 *
 * For more information please see http://www.janci.net/download/about/MHDv2
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License 3
 * @copyright Copyright (c) 2009, 2010 Jan Svantner
 * @package MHD
 */

// library has support only with UTF8 encode names
$starttime = microtime();
$startarray = explode(" ", $starttime);
$starttime = $startarray[1] + $startarray[0];


/**
 * include mhd.php for using class MHD
 */
require_once('libs/mhd.php');										//load library
$mhd = new MHD();													//create an instance of class MHD
#$mhd->setExpireTime(7*24*60);										//7*24*60 - default
#$mhd->setCacheType('FileCache');									//set type for caching
$zastavky = $mhd->getStops('10');									//get Stops for Bus 10
$pole = $mhd->getDepartures('31','Stodolova');  					//get departures for bus 31 on stop "Stodolova"
var_dump($pole);

$endtime = microtime();
$endarray = explode(" ", $endtime);
$endtime = $endarray[1] + $endarray[0];
$totaltime = $endtime - $starttime; 
$totaltime = round($totaltime,5);
echo "<br /><br /><center><font color='red'><small>This page loaded in $totaltime seconds.</small></font></center>";



