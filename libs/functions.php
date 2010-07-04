<?php
/**
 * MHD PHP Class.
 *
 * In this file are functions, which is required for class MHD
 *
 * For more information please see http://www.janci.net/about/mhd-class
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License 3
 * @copyright Copyright (c) 2009 Jan Svantner
 * @package MHD
 */

/**
 * @author Jan Svantner <janci@janci.net>
 * @link http://www.janci.net/about/mhd-class Project Home Page
 * @package MHD
 * @version 2.0 Development (20.12.2009)
 */

/**
 * Function for get content from page, where you require send data as POST
 *
 * @param string $url
 * @param string $get_query - format as GET parameters in browsers
 * @param string headers (deprecated)
 * @return string
 */
function file_post_contents($url, $get_query, $headers=false) {
    $url = parse_url($url);

    if (!isset($url['port'])) {
      if ($url['scheme'] == 'http') { $url['port']=80; }
      elseif ($url['scheme'] == 'https') { $url['port']=443; }
    }
    $url['query']=isset($url['query'])?$url['query']:'';

    $url['protocol']=$url['scheme'].'://';
	
	$t = $get_query;
	$get_query = $url['query'];
	$url['query'] = $t;
	
    $eol="\r\n";

	$url['query']='l='.$url['query'];
    $headers =  "POST ".$url['protocol'].$url['host'].$url['path'].'?'.$get_query." HTTP/1.0".$eol. 
                "Host: ".$url['host'].$eol. 
                "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol. 
                "Content-Type: application/x-www-form-urlencoded".$eol. 
                "Content-Length: ".strlen($url['query']).$eol.
                $eol.$url['query'];
	
    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30); 
    if($fp) {
      fputs($fp, $headers);
      $result = '';
      while(!feof($fp)) { $result .= fgets($fp, 128); }
      fclose($fp);
      if (!$headers) {
        //removes headers
        $pattern="/^.*\r\n\r\n/s";
        $result=preg_replace($pattern,'',$result);
      }
      return $result;
    }
}

     function webalize($s, $charlist = NULL, $lower = TRUE)
     {
         $s = strtr($s, '`\'"^~', '-----');
//         if (ICONV_IMPL === 'glibc') {
             setlocale(LC_CTYPE, 'en_US.UTF-8');
//         }
         $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s); // intentionally @
         $s = str_replace(array('`', "'", '"', '^', '~'), '', $s);
         if ($lower) $s = strtolower($s);
         $s = preg_replace('#[^a-z0-9' . preg_quote($charlist, '#') . ']+#i', '-', $s);
         $s = trim($s, '-');
         return $s;
     }

/**
 * Function error processes string as error message
 *
 * @param string
 */
function error($string = '') {
	echo $string."\n";
}


if (!function_exists('__')) {
	/**
	 * Function __ is alternative, if don't exist translate function for gettext
	 * Strings don't will translate
	 * 
	 * @param string
	 * @return string - translated
	 */
	function __($text) {return $text;} 
}

