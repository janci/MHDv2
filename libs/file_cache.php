<?php
require_once('functions.php');
/**
 * MHD PHP Class.
 *
 * In this source file is a class cache with Serialize data (using file in directory)
 *
 * For more information please see http://www.janci.net/download/about/MHDv2
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License 3
 * @copyright Copyright (c) 2009, 2010 Jan Svantner
 * @package MHD
 */

/**
 * @author Jan Svantner <janci@janci.net>
 * @link http://www.janci.net/download/about/MHDv2 Project Home Page
 * @package MHD
 * @version 2.0 Development (01.08.2010) alpha.4 (Testing version)
 * @property String $file name of filename for caching data
 */
class FileCache
{
	/**
	 * @var Array
	 */
	private $storage_data=array();
	/**
	 * @var String version of Cacher
	 */
	private $version='1.0.0';
	/**
	 * @var Array
	 */
	public $last_update=array();
	private $file;
	
	
	/**
	 * Overloading Property magic method
	 */
	public function __set($name, $value) {
		$this->storage_data[$name] = $value;
	}
	/**
	 * Overloading Property magic method
	 */
	public function __get($name) {
		if (isset($this->storage_data[$name]))
			return $this->storage_data[$name];
		else return null;
	}
	/**
	 * Overloading Property magic method
	 */
	public function __isset($name) {
		return isset($this->storage_data[$name]);
	}
	/**
	 * Overloading Property magic method
	 */
	public function __unset($name) {
		unset($this->storage_data[$name]);
	}
	
	/**
	 * Serialize array with getting data
	 * @return String
	 */
	public function serialize() {
		$this->storage_data['last_update'] = $this->last_update;
		$serializee_data = serialize($this->storage_data);
		return $serializee_data;
	}
	
	/**
	 * Serialize and save getting data to cache file
	 */
	public function save() {
		$data = self::serialize();
		if (!file_exists($this->file)) {
			$fp = fopen($this->file,'w+');
			fclose($fp);
		}
		file_put_contents($this->file, $data);
	}
	
	/**
	 * Unserialize string and set array this->last_update
	 * @return Object|Array
	 */
	public function unserialize($serialized_data) {
		$this->storage_data = NULL;
		$this->storage_data = unserialize($serialized_data);
		$this->last_update = $this->storage_data['last_update'];
		return $this->storage_data;
	}
	
	/**
	 * Load data from cache file, unserialize their and set their for get
	 */
	public function load() {
		if (file_exists($this->file)) {
			$serialize_data = file_get_contents($this->file);
			if (isset($serialize_data)&&$serialize_data) {
				$this->storage_data = self::unserialize($serialize_data);
			}
		}
	}
	
	/**
	 * Get cache filename
	 * @return String
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Set cache filename
	 * @param String
	 */
	public function setFile($file) {
		$this->file=$file;
	}
	
	/**
	 * Constructor for class FileCache
	 * @param String cache filename
	 */
	public function __construct($file=null) {
		if (!is_null($file)) $this->file = $file;
		else $this->file = dirname(__FILE__).'/cache/cache.tmp';
	}
}
