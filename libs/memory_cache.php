<?php 
require_once('functions.php');
/**
 * MHD PHP Class.
 *
 * In this source file is a class cache with Memcache (using RAM memory)
 *
 * For more information please see http://www.janci.net/about/mhd-class
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License 3
 * @copyright Copyright (c) 2009, 2010 Jan Svantner
 * @package MHD
 */

/**
 * @author Jan Svantner <janci@janci.net>
 * @link http://www.janci.net/about/mhd-class Project Home Page
 * @package MHD
 * @version 2.0 Development (20.12.2009) alpha.2 (Testing version)
 * @property Array $last_update info about last online update data
 */
class MemoryCache
{
	/**
	 * @var Array
	 */
	private $storage_data=array();
	/**
	 * @var String
	 */
	private $version='1.0.0';
	/**
	 * @var Memcache
	 */
	private $memcache;
	public $last_update=array();
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
	 * Save getting data to memory (RAM)
	 */
	public function save() {
		$this->storage_data['last_update'] = $this->last_update;
		if ($this->memcache->replace('storage_data',$this->storage_data)===false) $this->memcache->add('storage_data',$this->storage_data);
	}
	
	/**
	 * Load data from memory (RAM)
	 * 	@return Array data from memory
	 */
	public function load() {
		$this->storage_data = $this->memcache->get('storage_data');
		$this->last_update = $this->storage_data['last_update'];
		return $this->storage_data;
	}
	
	/**
	 * Constructor for class MemoryCache
	 */
	public function __construct() {
		$this->memcache = new Memcache;
		$this->memcache->connect("localhost", 11211);
	}
}
