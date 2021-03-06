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
 * @version    $Revision: 918 $
 */

use t41\Backend;
use t41\ObjectModel;
use t41\ObjectModel\Property;

/**
 * Abstract class for Backend adapters
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */


abstract class AdapterAbstract implements AdapterInterface {

	
	/**
	 * Backend Uri
	 *
	 * @var t41_Backend_Uri
	 */
	protected $_uri;
	
	
	/**
	 * Object <-> Backend Mapping Object
	 *
	 * @var t41_Backend_Mapper Mapper
	 */
	protected $_mapper;
	
	
	/**
	 * Ressource du backend
	 *
	 * @var mixed
	 */
	protected $_ressource;
	
	
	/**
	 * Array of comparison operators
	 * 
	 * Generally it is provided by the adapter itself, since it is closely bound to its query syntax
	 * 
	 * @var array
	 */
	protected $_operators = array();
	
	
	/**
	 * Class constructor
	 * 
	 * instanciate a new backend adapter based on parameters from the given t41_Backend_Uri instance.
	 * 
	 * @param \t41\Backend\BackendUri $uri
	 * @param string $alias
	 */
	public function __construct(Backend\BackendUri $uri)
	{
		// @todo perform some tests on given uri object
		$this->_uri = $uri;
	}
	
	
	/**
	 * Set mapper object instance to call on CRUD operations
	 * 
	 * @param \t41\Backend\Mapper $mapper
	 * @return \t41\Backend\Adapter\AdapterAbstract
	 */
	public function setMapper(Backend\Mapper $mapper)
	{
		$this->_mapper = $mapper;
		return $this;
	}
	
	
	public function getMapper()
	{
		return $this->_mapper;
	}
	
	
	protected function _getTableFromClass($class)
	{
		// get table to use
		if (! empty($this->_table)) {
			
			$table = $this->_table;
		
		} else if ($class && $this->_mapper instanceof Backend\Mapper) {
			
			$table = $this->_mapper->getDatastore($class);
			
		} else {
			
			if (strpos($class, '\\') !== false) {
				
				$table = substr($class, strrpos($class,'\\')+1);
				
			} else {
			
				$table = $class;
			}
		}

		return strtolower($table);
	}
	
	
	protected function _getTableFromUri(t41_Object_Uri $uri)
	{
		if ($uri->getUrl()) {
			
			$els = explode('/', $uri->getUrl());
			
			if (count($els) > 1 && $els[count($els)-2] != $this->_database) {
				
				return $els[count($els)-2];
				
			} else {
				
				return $this->_getTableFromClass($uri->getClass());
			}
			
		} else {

			return $this->_getTableFromClass($uri->getClass());
		}
	}
	
	
	public function buildObjectUri($url, $class)
	{
		$url = $this->_database . '/' . $this->_getTableFromClass($class) . '/' . $url;
		$uri = new ObjectModel\ObjectUri($url, $this->getUri());
		$uri->setClass($class);
		
		return $uri;
	}
	
	/**
	 * Returns backend's BackendUri instance
	 * 
	 * @return t41\Backend\BackendUri
	 */
	public function getUri()
	{
		return $this->_uri;
	}
	
	
	/**
	 * Returns backend's alias name
	 * 
	 * @return string
	 */
	public function getAlias()
	{
		return $this->_uri->getAlias();
	}

	
	public function find(ObjectModel\Collection $collection, $returnCount = false)
	{
		return $returnCount ? 0 : $collection;
	}
	

	public function returnsDistinct(ObjectModel\Collection $collection, PropertyAbstract $property)
	{
		return array();
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see \t41\Backend\Adapter\AdapterInterface::loadBlob()
	 */
	public function loadBlob(ObjectModel\DataObject $do, Property\AbstractProperty $property)
	{
		throw new Exception("The " . __METHOD__ . " method is not available in the " . __CLASS__ . " backend adapter");
	}
	
	
	/**
	 * Convert a given integer or string into an array of all operators definitions it contains
	 * 
	 * @param integer|string $operator
	 * @return array
	 * @throws t41_Exception
	 */
	protected function _matchOperator($operator)
	{
		if (! is_numeric($operator)) {
			
			$const = array_search($operator, $this->_operators);
			
			if ($const === false) {
				
				throw new \t41\Backend\Exception(array("CONDITION_UNDECLARED_OPERATOR", $operator));
			}
			
			return (array) $const;
		}
		
		$constants = array(Backend\Condition::OPERATOR_GTHAN, Backend\Condition::OPERATOR_LTHAN
						 , Backend\Condition::OPERATOR_EQUAL, Backend\Condition::OPERATOR_DIFF
						 , Backend\Condition::OPERATOR_BEGINSWITH, Backend\Condition::OPERATOR_ENDSWITH);

		$ops = array();
		
		foreach ($constants as $constant) {
			
			if (($constant & $operator) != 0) {
				
				$ops[] = $constant;
			}
		}

		return $ops;
	}
	
	
	/**
	 * Populate the given collection from the array of identifiers and the uri base
	 * 
	 * @param array $ids
	 * @param \t41\ObjectModel\Collection $collection
	 * @param \t41\ObjectModel\ObjectUri $uriBase
	 */
	protected function _populateCollection(array $ids, ObjectModel\Collection $collection, ObjectModel\ObjectUri $uriBase)
	{
		$class = $collection->getDataObject()->getClass();

		// populate array with relevant objects type
		$array = array();
		
		if ($collection->getParameter('memberType') != ObjectModel::URI) {
			
			$do = clone $collection->getDataObject();
		}

		foreach ($ids as $id) {
			
			$uri = clone $uriBase;
			$uri->setUrl($uri->getUrl() . $id)->setIdentifier($id);
                
            switch ($collection->getParameter('memberType')) {
            	
            	case ObjectModel::URI:
            		$obj = $uri;
            		break;
            		
            	case ObjectModel::MODEL:
            		$obj = clone $do;
            		$obj->setUri($uri);
            		$this->read($obj);
            		$obj = new $class(null, null, $obj);
            		break;
            		
            		
            	case ObjectModel::DATA:
            	default:
            		$obj = clone $do;
            		$obj->setUri($uri);
            		$this->read($obj);
            		break;
            }
            
            $array[] = $obj;
		}

		return $array;
	}
	
	
	protected function _add2History($literal, $data = null, array $context = array())
	{
		if (! isset($context[Backend::PREFIX])) $context[Backend::PREFIX] = $this->_uri->getAlias();
		Backend::add2History($literal, $data, $context);
	}
	
	
	public function getHistory()
	{
		return $this->_history;
	}
	
	
	public function getLastQuery()
	{
		return $this->_last;
	}
	
	
	protected function _setLastQuery($literal, $data = null, array $context = array())
	{
		$context['db'] 	= $this->_database;
		if (! isset($context['table'])) $context['table'] = $this->_table;
		$context[Backend::PREFIX] 	= $this->_uri->getAlias();
		Backend::setLastQuery($literal, $data, $context);
	}
	
	
	/**
	 * Return a key/value array of all backend keys needed to build a query for a unique record
	 * 
	 * @param t41_Data_Object $do
	 * @return array
	 */
	protected function _preparePrimaryKeyClauses(t41_Data_Object $do)
	{
		/* no mapper or no pkey definition in mapper */
		if (! $this->_mapper || ! $this->_mapper->getPrimaryKey($do->getUri()->getClass())) {
			
			return array(Backend::DEFAULT_PKEY => $do->getUri()->getIdentifier());
		}
		
		$array = array();

		// example of mapper definition: <mapper id="myid" pkey="key1:string,key2:integer">...</mapper>
		$pkeyVals  = explode(\t41\Mapper::VALUES_SEPARATOR, $do->getUri()->getIdentifier());
		
		/* @var $obj t41_Backend_Key */
		foreach ($this->_mapper->getPrimaryKey($do->getClass()) as $key => $obj) {
			
			if (! isset($pkeyVals[$key])) continue;
			
			$array[$obj->getName()] = $obj->castValue($pkeyVals[$key]);
		}
		
		return $array;
	}
}
