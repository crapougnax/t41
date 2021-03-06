<?php

namespace t41\Backend\Adapter;

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
 * @version    $Revision: 832 $
 */

use t41;
use t41\Backend;
use t41\ObjectModel;

/**
 * Abstract class providing all methods to use with XML files
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class XmlAdapter extends AbstractAdapter {


	/**
	 * XML file
	 *
	 * @var DomDocument
	 */
	protected $_ressource;
	

	protected $_rootName = 'items';
	
	
	protected $_itemName = 'item';
	
	
	protected $_pkey = 'id';
	
	
	protected $_file;
	
	
	protected $_operators = array(1 => '=', '!=', '>', '<', 'LIKE', 'NOT LIKE');

	/**
	 * Filesystem path
	 * @var string
	 */
	protected $_path;	
	
	
	
	/**
	 * Instanciate a XML backend from a t41_Backend_Uri object 
	 *
	 * @param t41_Backend_Uri $uri
	 * @param string $alias
	 * @throws t41_Backend_Exception
	 */
	public function __construct(Backend\BackendUri $uri, $alias = null)
	{
		parent::__construct($uri, $alias);
		
		if ($this->_uri->getUrl()) {
			
			$this->_path = $this->_uri->getUrl();
			
			$this->_path = str_replace('{basepath}', t41\Core::$basePath, $this->_path);
			if (substr($this->_path, -1) != DIRECTORY_SEPARATOR) {
				
				$this->_path .= DIRECTORY_SEPARATOR;
			}
		
		} else {
			
			throw new Exception('BACKEND_MISSING_PATH_PARAM');
		}
		
		if (! is_readable($this->_path)) {
			
			throw new Exception(array('BACKEND_PATH_UNREADABLE', $this->_path));
		}
		
		if (! is_writable($this->_path)) {
			
			throw new Exception(array('BACKEND_PATH_UNWRITABLE', $this->_path));
		}
	}
	
	
	/**
	 * Save new set of data from a t41_Data_Object object using INSERT 
	 *
	 * @param t41_Data_Object $do
	 * @return boolean
	 * @throws t41_Backend_Exception
	 */
	public function create(ObjectModel\DataObject $do)
	{
		if (! $this->_setRessource($do->getClass())) {
			
			die('BACKEND_RESSOURCE_ERROR');
		}
		
		// get a valid data array passing mapper if any
		if ($this->_mapper) {

			$recordSet = $do->map($this->_mapper);
			
		} else {
			
			$recordSet = $do->toArray();
		}
		
		$id = md5(microtime() . get_class($this));
		
		try {
			
			$root = $this->_ressource->getElementsByTagName($this->_rootName);
			$root = $root->item(0);
			
			if (! $root instanceof \DOMElement) {
				
				$root = $this->_ressource->createElement($this->_rootName);
				$this->_ressource->appendChild($root);
			}
			
			$node = $this->_ressource->createElement($this->_itemName);
			$node->setAttribute($this->_pkey, $id);
			
			foreach ($recordSet as $key => $value) {

				$tag = $this->_ressource->createElement($key, $value);
				$node->appendChild($tag);
			}
			
			$root->appendChild($node);
			
			$this->_saveRessource();
			
		} catch (Exception $e) {
			
			// @todo decide wether throw an exception or just save last message in a property
			return false;
		}
		
		$uri = $id;
		
		if (! $this->_mapper instanceof Backend\Mapper) {
			
			$uri = $this->_path . $this->_file . '/' . $id;
		}
		
		$uri = new ObjectModel\ObjectUri($uri);
		$do->setUri($uri);
		
		return true;
	}
	
	
	/**
	 * Retourne l'objet lu depuis le backend
	 *  
	 * @param t41_Uri $uri Uri des données à récupérer
	 * @param t41_Data_Object $do DataObject formaté pour son Objet
	 * @return t41_Data_Object DataObject formaté et rempli
	 */
	public function read(ObjectModel\DataObject $do) 
	{	
		if (! $this->_setRessource($do->getUri()->getClass())) {

			die('BACKEND_RESSOURCE_ERROR');
		}
		
		try {

			$xpath = new \DOMXPath($this->_ressource);
			$expr = sprintf("//%s[@%s='%s']", $this->_itemName, $this->_pkey, $do->getUri()->getIdentifier());
			$node = $xpath->query($expr)->item(0);

			$data = array();

			foreach ($node->getElementsByTagName('*') as $key => $val) {
				
				$data[$val->nodeName] = $val->nodeValue;
			}
			
		} catch (Exception $e) {
			
			die ($e->getMessage());
		}
		
		return $this->_mapper ? $this->_mapper->arrayToObject($data, $uri->getClass()) : $data;
	}
	
	
	
	/**
	 * Enregistre les modifications de l'Objet correspondant au DataObject donné en paramètres
	 *
	 * @param t41_Data_Object $do
	 * @return mixed
	 */
	public function update(ObjectModel\DataObject $do)
	{
		if (! $this->_setRessource($do->getClass())) {

			die('BACKEND_RESSOURCE_ERROR');
		}
		
		try {
			
			$uri = $do->getUri();

			$xpath = new \DOMXPath($this->_ressource);
			$expr = sprintf("//%s[@%s='%s']", $this->_itemName, $this->_pkey, $uri->getIdentifier());
			$node = $xpath->query($expr)->item(0);

			// prepare data
			$data = $this->_mapper ? $this->_mapper->objectToArray($do->toArray(), $do->getClass()) : $do->toArray();

			// update existing nodes
			foreach ($node->getElementsByTagName('*') as $val) {
				
				if (isset($data[$val->nodeName])) {
					
					$val->nodeValue = $data[$val->nodeName];
					unset($data[$val->nodeName]);
				}
			}
			
			// then add new data
			foreach ($data as $key => $val) {
				
				$elem = $this->_ressource->createElement($key, $val);
				$node->appendChild($elem);
			}
			
			$this->_saveRessource();
			
		} catch (Exception $e) {

			die ($e->getMessage());
		}
		
		return true;
	}
	
	
	/**
	 * Efface la ligne la base identifié par l'Uri en paramètre
	 *
	 * @param t41\ObjectModel\DataObject $do
	 * @return mixed
	 */
	public function delete(ObjectModel\DataObject $do)
	{		
	}
	
	
	public function find(ObjectModel\Collection $collection)
	{
		$class = $collection->getDataObject()->getClass();
		$mode  = $collection->getParameter('memberType');

		$expr = '';
		
		$this->_setRessource($class);
		
		/* @var $condition t41_Condition */
		foreach ($collection->getConditions() as $conditionArray) {
			
			$condition = $conditionArray[0];
			
			// map property to field
			if ($this->_mapper) {

				$field = $this->_mapper->propertyToDatastoreName($class, $condition->getProperty()->getId());
			
			} else {

				$field = $condition->getProperty()->getId();				
			}

			if ($expr) {
				
				switch ($conditionArray[1]) {
				
					case 'OR':
						$expr .= ' or ';
						break;
					
					case 'AND':
					default:
						$expr .= ' and ';
						break;
				}
			}
			
			$expr .= sprintf("%s %s '%s'"
										, $field
										, is_numeric($condition->getOperator()) ? $this->_operators[$condition->getOperator()] : $condition->getOperator()
										, $condition->getValue()
							);
		}

		// get all nodes id
		$result = $this->_findNodes($expr, '@id');
		
		$dataSet = array();
		
		foreach ($result as $node) {
			
			$dataSet[] = $node->nodeValue;
		}
		
		if (count($collection->getSortings()) > 0) {

			$sort = array();
			
			foreach ($collection->getSortings() as $key => $sorting) {
			
				if ($this->_mapper) {

					$field = $this->_mapper->propertyToDatastoreName($class, $sorting[0]->getId());
			
				} else {

					$field = $sorting[0]->getId();				
				}
				
				$sort = $this->_sortNodes($sort, $field, $sorting[1], ($key == 0) ? $dataSet : null);
			}
			
		}

		
		// Flatten array
		$sort = $this->_arrayflat($sort);
		
		//Zend_Debug::dump($sort);
		
		
		$array = array();
		
		$uri = new ObjectModel\ObjectUri();
		$uri->setBackendUri($this->_uri);
		$uri->setClass($class);

		if ($mode != 'uri') {
			
			$do = new ObjectModel\DataObject($class);
		}
		
		
		$count = $collection->getBoundaryOffset();
		$limit = $count + $collection->getBoundaryBatch();
		
		/* iterate over result set as long as requested */
		while ($count < $limit) {
			
			if (! isset($dataSet[$count])) {
				
				// if end of result data set has been reached, return array
				return $array;
			}
			
			$id = $dataSet[$count];
			
			$uri->setUrl($this->_alias . '/' . $id);
			switch ($mode) {

				case 'uri':
					$data = clone $uri;
					break;
					
				case 'data':
					$do->setUri(clone $uri);
					$do->populate();
					$data = clone $do;
					break;
					
				case 'model':
					$do->setUri(clone $uri);
					$do->populate();
					/* @var $obj t41_Object_Model */
					$data = new $class(null, null, clone $do);
					break;
			}
			
			$array[] = $data;
			$count++;
		}
		
		return $array;
	}

	
	/**
	 * Set a new DomDocument ressource based on given $className and other parameters
	 * 
	 * @param string $className
	 * @return boolean
	 */
	protected function _setRessource($className)
	{
		// get file to use, from mapper if available, else from data object
		$this->_file = ($this->_mapper instanceof Backend\Mapper) ? $this->_mapper->getDatastore($className) : $className;
		$this->_file = strtolower($this->_file) . '.xml';
		
		// primary key is either part of the mapper configuration or 'id'
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($className) : 'id';

		$this->_itemName = ($this->_mapper instanceof Backend\Mapper) ? $this->_mapper->getDatastore($className) : 'item';
		$this->_rootName = $this->_itemName . 's';
		
		try {

			/* @todo implement flock() to lock file during updating */
			$this->_ressource = new \DOMDocument('1.0', 'UTF-8');
			if (file_exists($this->_path . $this->_file)) {
				
				$this->_ressource->load($this->_path . $this->_file);
				
			} 
		} catch (Exception $e) {
			
			return false;
			die ($e->getMessage());
		}
		
		return true;
	}
	
	
	/**
	 * Save current DomDocument to the filesystem
	 * 
	 * @throws t41_Backend_Exception
	 */
	protected function _saveRessource()
	{
		if (! $this->_ressource instanceof \DOMDocument) {
			
			throw new Exception("BACKEND_RESSOURCE_NO_RESSOURCE");
		}
		
		$this->_ressource->formatOutput = true;
		$this->_ressource->save($this->_path . $this->_file);
	}
	
	
	/**
	 * Find the node matching the given id and returns it
	 * 
	 * @param string $id
	 * @param string $field
	 * @param string $className
	 * @return DOMElement
	 */
	protected function _findNode($id, $field, $className = null)
	{
		if (! $this->_ressource instanceof \DOMDocument && ! is_null($className)) {
			
			$this->_setRessource($className);
		}
		
		$xpath = new \DOMXPath($this->_ressource);
		$expr = sprintf("//%s[@%s='%s']%s", $this->_itemName, $this->_pkey, $id, $field ? '/' . $field : null);

		return $xpath->query($expr)->item(0);
	}
	
	
	protected function _getNodeValue($id, $field)
	{
		$node = $this->_findNode($id, $field);
		return ($node instanceof \DOMElement) ? $node->nodeValue : null;
	}
	
	
	protected function _findNodes($expr = null, $field = null, $className = null)
	{
		if (! $this->_ressource instanceof \DOMDocument && ! is_null($className)) {
			
			$this->_setRessource($className);
		}
		
		if ($expr) $expr = '[' . $expr . ']';
		
		$xpath = new \DOMXPath($this->_ressource);
		$expr = sprintf("//%s%s%s", $this->_itemName, $expr, $field ? '/' . $field : null);
//echo $expr;
		return $xpath->query($expr);
	}
	
	
	protected function _sortNodes($sort = array(), $field, $order, $initarray = null)
	{
		$array = is_array($initarray) ? $initarray : $sort;
		
		foreach ($array as $id => $data) {

			if (is_array($data)) {
				
				// go recursive!
				$sort[$id] = $this->_sortNodes($data, $field, $order);
			
			} else if (is_numeric($id)) {
			
				$val = $this->_getNodeValue($data, $field);
				
				if (isset($sort[$val])) {

					if (! is_array($sort[$val])) {
							
						$sort[$val] = (array) $sort[$val];
					}
					$sort[$val][] = $data;
				
				} else {
					
					$sort[$val] = $data;
				}
				
				unset($sort[$id]);
			}
		}
		
		/* sort by key value which contains sorting data */
		ksort($sort);
		
		return $sort;
	}
	
	
	protected function _arrayflat($array)
	{
		foreach ($array as $key => $value) {
			
			if (is_array($value)) {
				
				$array += $this->_arrayflat($value);
				unset($array[$key]);
			
			} else {
				
				$array[$value] = null;
				unset($array[$key]);
			}
		}
		
		return $array;
	}
}
