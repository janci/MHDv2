<?php 
if (file_exists('libs/functions.php')) require_once ('functions.php');
if (file_exists('libs/memory_cache.php')) require_once ('memory_cache.php');
if (file_exists('libs/dummy_cache.php')) require_once('dummy_cache.php');
require_once ('file_cache.php');
/**
 * MHD PHP Class.
 *
 * This source file is a class for generating and getting information for connexion
 * in City Kosice, Slovakia.
 *
 * For more information please see http://www.janci.net/about/mhd-class
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License 3
 * @copyright Copyright (c) 2009, 2010 Jan Svantner
 * @package MHD
 */

/**
 * @author Jan Svantner <janci@janci.net>
 * @author Peter Piatničko <peterpiatnicko@zoznam.sk>
 * @link http://www.janci.net/about/mhd-class Project Home Page
 * @final
 * @package MHD
 * @version 2.0 Development (20.12.2009) alpha.2 (Testing version)
 * @property string $links url link for parsing
 * @property integer $expire_time how long be data cached
 * @todo TODO: for PO|BA etc.
 */
final class MHD
{
	private $link;
	/**
	 * @var array
	 */
	private $stops;
	/**
	 * @var string
	 */
	private $form_url;
	/**
	 * @var array
	 */
	private $spoje;
	/**
	 * @var array
	 */
	private $categories;
	/**
	 * @var array
	 */
	private $departures;
	/**
	 * @var FileCache
	 */
	private $cache;
	/**
	 * @var integer
	 */
	private $expire_time=604800;
	/**
	 * @var boolean
	 */
	private $checkedCache=false;
	/**
	 * @var string|ICache
	 */
	private $cacheType=null;
	
	
	/**
	 * Method, what return actual connect url
	 * 
	 * @return string
	 */
     public function getLink() {return $this->link;}
	
	/**
	 * Method, which setting parameter link (actual connect url - for BA|BB is different)
	 *
	 * @param string (url format)
	 */
    public function setLink($newLink) {$this->link=$newLink;}	
	
	/**
	 * Method, which control caching data before connect to MHD server
	 */	
	private function cacher() {
		if (is_null($this->cache)) {
			if (class_exists('Memcache') && $this->cacheType!=='filecache') 
				$this->cache = new MemoryCache();
			else
				$this->cache = new FileCache(dirname(__FILE__).'/../cache/'.md5($this->link).'.tmp');
		}
		if ($this->cacheType=='filecache')
			$this->cache = new FileCache(dirname(__FILE__).'/../cache/'.md5($this->link).'.tmp');
			
		$this->cache->load();
		$cache_expire = $this->expire_time; //seconds
		
		if (!isset($this->cache->last_update) || !isset($this->cache->last_update['spoje']) || ($this->cache->last_update['spoje']+$cache_expire)<time()) unset($this->cache->spoje);
		if (!isset($this->cache->last_update) || !isset($this->cache->last_update['form_url']) || ($this->cache->last_update['form_url']+$cache_expire)<time()) unset($this->cache->form_url);
		
		if (isset($this->cache->last_update) && isset($this->cache->last_update['departures'])) {
			foreach ($this->cache->last_update['departures'] as $spoj=>$cas) {
				if (($cas+$cache_expire)<time()) {
					unset($this->cache->departures[$spoj]);
					unset($this->cache->categories[$spoj]);
				}
			}
		} else {
			unset($this->cache->departures[$spoj]);
			unset($this->cache->categories[$spoj]);
		}
		
		if (isset($this->cache->last_update) && isset($this->cache->last_update['stops'])) {
			foreach ($this->cache->last_update['stops'] as $spoj=>$cas) {
				if (($cas+$cache_expire)<time()) unset($this->cache->stops[$spoj]);
			}
		} else {
			unset($this->cache->stops[$spoj]);
		}
		
		if (isset($this->cache->stops)) $this->stops = $this->cache->stops;
		if (isset($this->cache->departures)) $this->departures = $this->cache->departures;
		if (isset($this->cache->spoje)) $this->spoje = $this->cache->spoje;
		if (isset($this->cache->form_url)) $this->form_url = $this->cache->form_url;
		if (isset($this->cache->categories)) $this->categories = $this->cache->categories;
		self::online_map();
		$this->checkedCache=true;
	}
	
	/**
	 * Method what scan page `http://imhd.sk/ke/` and get require variable [onload]
	 * 
	 * @return bool
	 */
	private function online_map()
	{
		if (isset($this->spoje) && isset($this->form_url)) return true;
		$link = $this->link;
		$content = file_get_contents($link);
		if ($content===false) {
			error(__('Class MHD can\'t connect to http://www.imhd.sk'));
			return false;
		}
		
		if (preg_match("#'Cestovn. poriadky','([^']*)'#",$content, $match)) {
			$cestovne_poriadky = $match['1'];
			unset($content);
		} else {
			error(__('Can\'t get link for `Cestovné poriadky` on Home Page'));
			return false;
		}
		
		$content = file_get_contents($link.$cestovne_poriadky);
		unset($cestovne_poriadky);
		if ($content===false) {
			error(__('Class MHD can\'t connect to link: `'.$cestovne_poriadky.'`'));
			return false;
		}
		
		if (preg_match('#<form [^>]*action="([^"]*)?"[^>]*name="cestovnyporiadok">#',$content,$match)) {
			if ($this->form_url != $match['1']) {
				$this->form_url = $match['1'];
				$this->cache->last_update['form_url'] = time();
			}
		} else {
			error(__('Can\'t form link on `Cestovné poriadky` Page'));
			return false;
		}
		
		if (preg_match_all('#<option value="([^"]*)?">([^«][^ <]*)#s',$content,$match, PREG_SET_ORDER)) { 
			foreach ($match as $it) {
				$this->spoje[$it['2']] = array('cislo'=>$it['2'], 'url'=>$it['1']);
				$this->cache->last_update['spoje'] = time();
			}
		} else {
			error(__('I can\'t anything bus on Page, please contact author of this project'));
			return false;
		}
		return true;
	}
	
	/**
	 * Get departures for bus in specific stop and for specific direction
	 * @param string $number_of_service
	 * @param string $name_of_stop (utf8 encoding)
	 * @param integer $direction (optional) 1 or 0
	 * @return false|array array of stops or error
	 */
	public function getDepartures($number_of_service, $name_of_stop, $direction=0) {
		if ($this->checkedCache===false) self::cacher();
		if (isset($this->departures[$number_of_service])) return $this->departures[$number_of_service];
		$a = self::getStops($number_of_service);
		$name_of_stop = iconv('utf-8','windows-1250',$name_of_stop);
	    //var_dump($this->stops[$number_of_service]);
		$url = $this->link.$this->stops[$number_of_service][$direction][$name_of_stop]['url'];
		//echo $this->link; exit;
		if ($url===$this->link && $a === false) {
			error(__('I can\'t find departure, which you find!'));
			return false;
		}
		if ($url===$this->link) {
			error(__('Departure is unknown!'));
			return false;
		}
		
		$content = file_get_contents($url);	
		if ($content===false) {
			error(__('Content of departure not found!'));
			return false;
		}
		
		$content = str_replace(array('<span class="cp_nizkopodlazne">','</span>'),"",$content);
		
		
		preg_match('#td id="stred">(.*)<div class="netlacit"#s',$content, $match);
		preg_match('$.*<td style="border: 0px" width="100%"><table style="border: 0px" class="tab" border="0" cellpadding="0" cellspacing="0" align="center" width="100%">(.*)$s',$match['1'],$match2);
		
		
		$content = str_replace('</center>',"</center>\n",$match2[1]);
		$content = str_replace('<center>',"\n<center>",$content);
		
		/* get category */
		unset($match);
		preg_match_all('#<center><b>(<font.*)</b></center>#',$content,$match);
		if (!isset($match['1']) || (count($match['1'])==0)) {
			error(__('Found zero categories for service!')); # system error
			return false;
		}
		$count_of_category = count($match['1']);
		foreach ($match['1'] as $item) $category[] = html_entity_decode(strip_tags($item));
		$this->categories[$number_of_service] = $category;
		/* end get category */
		
		/* get minutes */
		unset($match);		
		if (!preg_match_all('#<font style="font-size: 10pt">([0-9]+)</font>.*><td class#sU',$content, $match)) echo ""; // "><b>
		
		//preg_match_all('#<font style="font-size: 10pt">([0-9]+)</font>.*"><b>#sU',$content, $match); //for PO
		//preg_match_all('#<font style="font-size: ([0-9]{1,2})pt">([0-9]+)</font>.*"><b>#sU',$content, $match);
		
		$j=0;
		for ($i=0;$i<count($match['1']);$i++) {
			preg_match_all('#\.</font>&nbsp;([0-9]+([a-zA-Z]){0,2})#',$match['0'][$i],$hour_dep);
			if (isset($final[$j][$match['1'][$i]])) $j++;
			$final[$category[$j]][$match['1'][$i]] = $final[$j][$match['1'][$i]] = $hour_dep['1'];
			unset($hour_dep);
		}
		/* end get minutes */
		$this->departures[$number_of_service] = $final;
		$this->cache->last_update['departures'][$number_of_service] = time();
		return $final;
		
	}
	
	/**
	 * Method, which return stops for service, which number is than input 
	 * 
	 * @param string $number_of_service
	 * @return false|array array of stops or error
	 */
	public function getStops($number_of_service) {
		if ($this->checkedCache===false) self::cacher();
		if (isset($this->stops[$number_of_service])) return $this->stops[$number_of_service];
		
		if (!isset($this->spoje) || (empty($this->spoje))) {
			error(__('Array `spoje` is unset.'));
			return false;
		}
		if (isset($this->spoje[$number_of_service])) {
			$content = file_post_contents($this->link.$this->form_url, $this->spoje[$number_of_service]["url"]);
		} else {
			error(__('Service `'.$number_of_service.'` is unknown!'));
			return false;
		} 
		
		if ($content===false) {
			error(__('Can\'t connect to link service page'));
			return false;
		}
		
		if (!preg_match_all('#<a href="index.php?([^l]*l=[^"]*)">([^<]*)</a>#s',$content, $match, PREG_SET_ORDER)) {
			error(__('Can\'t get names of service!'));
			return false;
		}
		$t = 0;
		foreach ($match as $it) {
			if ($it['2']=='odchody' || preg_match('#najbli..ie#',$it['2'])) continue;
			if (isset($this->stops[$number_of_service][$t][$it['2']])) {
				$t = 1;
				$this->stops[$number_of_service][1][$last] = $this->stops[$number_of_service][0][$last];
				unset($this->stops[$number_of_service][0][$last]);
			}
			$this->stops[$number_of_service][$t][$it['2']] = array('stop'=>$it['2'],'url'=>$it['1']);
			$last = $it['2'];
		}
		$this->cache->last_update['stops'][$number_of_service] = time();
		return $this->stops[$number_of_service];
	}
	
	/**
	 * In destructor saving getting data to cache - file
	 */
	public function __destruct() {	
		if (!is_null($this->cache))	{
			$this->cache->stops = $this->stops;
			$this->cache->departures = $this->departures;
			$this->cache->spoje = $this->spoje;
			$this->cache->form_url = $this->form_url;
			$this->cache->categories = $this->categories;
			$this->cache->save();
		}
	}
	
	/**
	 * In constructor is mapper for page `http://imhd.sk/ke/`
	 * and load cache data
	 * @param String $link url address for parse data
	 */
	public function __construct($link='http://imhd.zoznam.sk/ke/') {
		$this->link = $link;
	}
	
	/**
	 * Set expire time for cache in seconds
	 * 
	 * @paraqm integer $time Time in seconds
	 */
	public function setExpireTime($time) {
		$this->expire_time = $time;
	}
	
	/**
	 * Set object for caching
	 * 
	 * @param string|ICache
	 * @ignore
	 */
	public function setCacheType($type) {
		if (is_object($type)) {
			$this->cache = &$type;
			$this->cacheType = 'ICache';
		} else {
			switch(strtolower($type)) {
				case 'memorycache':
					$this->cacheType = 'memorycache';
					$this->cache = new MemoryCache();
					break;
				case 'filecache':
					$this->cacheType = 'filecache';
					break;
				case 'dummycache':
					$this->cacheType = 'dummycache';
					$this->cache = new DummyCache();
					break;
				default:
					error(__('Unknown Cache Type "'.$type.'". Use one of { "MemoryCache","FileCache","DummyCache",instance for interface ICache }!'));
					exit();
			}
		}
	}
}
