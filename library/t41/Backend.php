<?php

namespace t41;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 916 $
 */

use t41\Backend,
	t41\Backend\Adapter;

/**
 * Class providing exchange interface with data sources
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Backend {

	
	/**
	 * Prefix to prepend to a backend alias so it's recognized as such in an URI
	 * 
	 * @var string
	 */
	const PREFIX = '@';
	
	
	/**
	 * Default identifier holder name
	 * 
	 * @var string
	 */
	const DEFAULT_PKEY = 'id';
	
	
	const MAX_ROWS_IDENTIFIER = 'MAX_ROWS';
	
	
	/**
	 * Array holding parameters of all defined backends
	 * 
	 * @var array
	 */
	static protected $_config;
	
	
	/**
	 * Array of all defined mappers
	 * @var array
	 */
	static protected $_backendsMappers = array();
	
	/**
	 * Array of all backends aliases
	 * @var array
	 */
	static protected $_backendsAlias = array();
	
	/**
	 * Array of all instances of backends
	 * @var array
	 */
	static protected $_backendsObj = array();
	
	/**
	 * Key value of default backend
	 * @var string
	 */
	static protected $_default;
	
	
	static protected $_history = array();
	
	
	static protected $_debug = false;
	

	/**
	 * Load a configuration file and add or replace content
	 * 
	 * @param string $file name of file to parse, file should be in application/configs folder
	 * @param boolean $add wether to add to (true) or replace (false) existing configuration data
	 * @return boolean true in case of success, false otherwise
	 */
	static public function loadConfig($file = 'backends.xml', $add = true)
	{
		// load config file (file extension defines which adapter will be used)
		$config = Config\Loader::loadConfig($file);

		if ($config === false) {
			
			return false;
		}

		if (! isset($config['backends'])) {
			
		//	\Zend_Debug::dump($config);
			throw new Config\Exception("NO_CONFIG_IN_GIVEN_SOURCE");
		}
		
		// if the key 'default' exists, it defines the default backend key value
		if (isset($config['backends']['default'])) {
			
			self::setDefault($config['backends']['default']);
			unset($config['backends']['default']);
		}
		
		if ($add === false) {
        
			self::$_config = $config['backends'];
		
		} else {
			
	        self::$_config = array_merge((array) self::$_config, $config['backends']);
		}
		
		return true;
	}
	
	
	/**
	 * Define default backend from its alias name
	 *
	 * @param string $backend
	 */
	static public function setDefault($backend)
	{
		if (is_string($backend)) {
			
			self::$_default = $backend;
		}
	}
	

	/**
	 * Returns an instance of the default backend adapter
	 *
	 * @return t41_Backend_Adapter_Abstract
	 */
	static public function getDefaultBackend()
	{
		return self::getInstance(self::PREFIX . self::$_default);
	}
	
	

	/**
	 * Returns the t41_Backend_Uri of the given backend alias
	 * 
	 * @param string $key
	 * @return t41_Backend_Uri
	 */
	static public function getBackendUri($key = null)
	{
		if (! is_null($key)) {
			
			if (substr($key, 0, 1) == self::PREFIX) {
				
				$key = substr($key, 1);
			}
		} else {
			
			$key = self::$_default;
		}
		
		return self::getInstance($key)->getUri();
	}
	
	
	/**
	 * Recupérer une instance de Backend à partir de son Uri, alias ou encore id dans la liste.
	 *
	 * @param string|t41_Backend_Uri alias or uri of desired backend
	 * @return t41\Backend\Adapter\AbstractAdapter Backend Adapter
	 */
	static public function getInstance($id)
	{
		if (! is_array(self::$_config)) {

			self::loadConfig();
		}
		
		if ($id instanceof Backend\BackendUri) {
			
			if ($id->getAlias()) {

				return self::getInstance($id->getAlias());					
			
			} else if ($id->getHost() && $id->getType()) {

				// si uri n'est pas un alias mais contient au moins host + type
				return self::factory($id);
			}
			
		} else {
			
			if (substr($id, 0, 1) == self::PREFIX) {
				
				$id = substr($id, 1);
			}

			/* return already instanciated backend */
			if (isset(self::$_backendsObj[$id])) {
					
				return self::$_backendsObj[$id];
			}
			
			if (isset(self::$_config[$id])) {

				$config = self::$_config[$id];
				$uri = array();
				
				/* temp fix - some backends require adapters (PDO), most don't */
				if (! isset($config['uri']['adapter'])) {
					
					$uri['adapter'] = $config['type'];
				}
				
				/* reduce possible arrays into proper env-based value */
				/* @todo use parent value if backend extends some other backend */ 
				foreach ($config['uri'] as $key => $val) {

					if (is_array($val)) {
						
						$uri[$key] = isset($val[Core::$env]) ? $val[Core::$env] : null;
						
					} else {
						
						$uri[$key] = $val;
					}
				}
				
				$uri = new Backend\BackendUri($uri);
				
				$backend = self::factory($uri, $id);
				
				/* @todo refactor this */
				if (isset($config['mapper'])) {
						
					if (is_array($config['mapper'])) {

						if (isset($config['mapper'][Core::getEnvData('webEnv')])) {
								
							$mapper = $config['mapper'][Core::getEnvData('webEnv')];
								 
						} else {
								
							throw new Backend\Exception("NO_MAPPER_VALUE");
						}
					} else {
						
						$mapper = $config['mapper'];
					}
						
					$mapper = Mapper::getInstance($mapper);
					$backend->setMapper($mapper);
				}
				
				return $backend;
			}
		}
		
		throw new Backend\Exception('Unknown backend alias: ' . $id . '.');
	}
	
	
	/**
	 * Instanciate a backend from its uri
	 *
	 * @param t41_Uri $uri
	 * @param string $alias		alias name
	 * @param string $mapper mapper name
	 * @return t41\Backend\Adapter\AbstractAdapter
	 * @throws t41\Backend\Exception
	 */
	static public function factory(Backend\BackendUri $uri, $alias = null, $mapper = null)
	{
		if (! is_null($alias)) $uri->setAlias($alias);
		
		$parts = explode('_', $uri->getType());
		foreach ($parts as $key => $part) {
			
			$parts[$key] = ucfirst(strtolower($part));
		}
		
		$backendClass = sprintf('\t41\Backend\Adapter\%sAdapter', implode('\\', $parts));
		
		try {
			
			$backend = new $backendClass($uri);
			$alias = self::addBackend($backend, $alias);
			if ($mapper) {
				
				$backend->setMapper(Backend\Mapper::getInstance($mapper));
			}
			
			return $backend;
			
		} catch(\Exception $e) {
			
			throw new Backend\Exception('UNKNOWN_CLASS');
		}
	}

	
	/**
	 * Add a backend adapter instance to the store
	 *
	 * @param t41_Backend_Adapter_Abstract $backend
	 * @param string $alias
	 * @return int
	 */
	static public function addBackend(Backend\Adapter\AbstractAdapter $backend, $alias = null)
	{
		if (empty($alias)) {
			
			$alias = count(self::$_backendsObj);
		}
		
		self::$_backendsObj[$alias] = $backend;
		
		return $alias;
	}
	
	
	/**
	 * Récuperer des données du backend à partir d'une Uri et les insérer dans un DataObject (typé ou non).
	 *
	 * @param t41_Data_Object $do Data object to populate
	 * @param t41_Backend_Adapter_Abstract $backend
	 * @return boolean
	 */
	static public function read(ObjectModel\DataObject $do, Backend\Adapter\AbstractAdapter $backend = null)
	{
		if (is_null($backend)) {

			if ($do->getUri()) {
			
				/* if uri is not empty, get backend information from it */
				$backend = self::getInstance($do->getUri()->getBackendUri());

			} else {
				
				/* get object definition default backend */
				$backend = ObjectModel::getObjectBackend($do->getClass());
			}
				
			if (is_null($backend)) {

				/* get object default backend if exists */
				$backend = self::getDefaultBackend();
			}
		}
			
		if (! $backend) {

			throw new Exception("NO_AVAILABLE_BACKEND");
		}
			
		if ($do->getUri()) {
				
			// populate data object in backend adapter and return status
			return $backend->read($do);
			
		} else {

			throw new Backend\Exception("NO_AVAILABLE_URI");
		}
	}
	
	
	
	/**
	 * Save object in given backend, class default backend or global default backend.
	 *
	 * @param t41\ObjectModel\DataObject $do
	 * @param t41\Backend\Adapter\AbstractAdapter $backend
	 */
	static public function save(ObjectModel\DataObject $do, Backend\Adapter\AbstractAdapter $backend = null)
	{
		if (is_null($backend)) {

			if ($do->getUri()) {
			
				/* uri n'est pas vide, on peu alors essayer d'y trouver le backend */
				$backend = self::getInstance($do->getUri()->getBackendUri());

			} else {
				
				/* get object definition default backend */
				$backend = ObjectModel::getObjectBackend($do->getClass());
			}
				
			if (is_null($backend)) {

				/* get object default backend if exists */
				$backend = self::getDefaultBackend();
			}
		}
			
		if (! $backend) {

			throw new Backend\Exception("NO_AVAILABLE_BACKEND");
		}
			
		if ($do->getUri()) {
				
			// Update de l'objet dans le backend
			return $backend->update($do);
			
		} else {
			
			// Insertion de l'objet dans le backend
			return $backend->create($do);
		}
	}
	

	static public function populate(t41_Data_Object $do)
	{
		
		return $do;
	}
	
	/**
	 * Delete the given data object in its backend
	 * 
	 * @param ObjectModel\DataObject $do
	 * @param Backend\Adapter\AbstractAdapter $backend
	 * @throws Backend\Exception
	 */
	public static function delete(ObjectModel\DataObject $do, Backend\Adapter\AbstractAdapter $backend = null)
	{
		if (is_null($backend)) {

			if ($do->getUri()) {
			
				/* uri n'est pas vide, on peu alors essayer d'y trouver le backend */
				$backend = self::getInstance($do->getUri()->getBackendUri());

			} else {
				
				/* get object definition default backend */
				$backend = ObjectModel::getObjectBackend($do->getClass());
			}
				
			if (is_null($backend)) {

				/* get object default backend if exists */
				$backend = self::getDefaultBackend();
			}
		}
			
		if (! $backend) {

			throw new Backend\Exception("NO_AVAILABLE_BACKEND");
		}
			
		return $backend->delete($do);
	}	
	
	
	/**
	 * Execute a search on given backend with given t41_Object_Collection parameters
	 * and returns an array of results (either t41_Object_Uri, t41_Object_Data or t41_Object_Model instances)
	 * 
	 * @param t41\ObjectModel\Collection $co
	 * @param t41\Backend\Adapter\AbstractAdapter $backend
	 * @param array|boolean $returnCount 
	 * @throws t41\Backend\Exception
	 * @return array
	 */
	static public function find(ObjectModel\Collection $co, Backend\Adapter\AbstractAdapter $backend = null, $returnCount = false)
	{
		/*
		 * Backend to use in order of preferences
		 * 
		 * 1. current method backend argument if not null
		 * 2. object default backend
		 * 3. general default backend
		 */
		
		if (is_null($backend)) {
			
			$backend = ObjectModel::getObjectBackend($co->getDataObject()->getClass());
			
			if (is_null($backend)) {
			
				// get default backend
				$backend = self::getDefaultBackend();
			}
		}
			
		if (! $backend) {

			throw new Backend\Exception("NO_AVAILABLE_BACKEND");
		}

		return $backend->find($co, $returnCount);
	}
	
	
	static public function stats(ObjectModel\Collection $co, Backend\Adapter\AbstractAdapter $backend = null, array $properties)
	{
		if (is_null($backend)) {
				
			$backend = ObjectModel::getObjectBackend($co->getDataObject()->getClass());
				
			if (is_null($backend)) {
					
				// get default backend
				$backend = self::getDefaultBackend();
			}
		}
			
		if (! $backend) {
		
			throw new Backend\Exception("NO_AVAILABLE_BACKEND");
		}
		
		return $backend->find($co, $properties);
	}
	
	
	static public function returnsDistinct(ObjectModel\Collection $co, Property\PropertyAbstract $property, Backend\Adapter\AbstractAdapter $backend)
	{
		if (is_null($backend)) {
			
			$backend = ObjectModel::getObjectBackend($co->getDataObject()->getClass());
			
			if (is_null($backend)) {
			
				// get default backend
				$backend = self::getDefaultBackend();
			}
		}
			
		if (! $backend) {

			throw new Backend\Exception("NO_AVAILABLE_BACKEND");
		}

		return $backend->returnsDistinct($co, $property);		
	}
	
	

	/*
	 * QUERY HISTORY METHODS
	 */
	
	
	static public function add2History($literal, $data = null, $context = null)
	{
		self::$_history[] = array('query' => $literal, 'data' => $data, 'context' => $context);
	}
	
	
	static public function getHistory()
	{
		return self::$_history;
	}
	
	
	static public function getLastQuery()
	{
		return count(self::$_history) > 0 ? self::$_history[count(self::$_history)-1] : null;
	}
	
	
	static public function setLastQuery($literal, $data = null, $context = null)
	{
		$key = self::$_debug ? count(self::$_history) : 0;
		self::$_history[$key] = array('query' => $literal, 'data' => $data, 'context' => $context);
	}
	
	
	static public function setDebug($bool)
	{
		self::$_debug = (bool) $bool;
	}
}
