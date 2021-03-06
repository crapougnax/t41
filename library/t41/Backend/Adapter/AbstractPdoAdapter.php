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
 */


use t41\Backend;
use t41\Backend\Key;
use t41\Backend\Condition;
use t41\ObjectModel;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\Property;
use t41\ObjectModel\Property\DateProperty;
use t41\ObjectModel\Property\ObjectProperty;
use t41\Core;
use t41\ObjectModel\Property\MetaProperty;
use t41\ObjectModel\Collection;

/**
 * Abstract class providing all CRUD methods to use with PDO adapters
 * 
 * Must be inherited by an adapter-binded class (ex: t41_Backend_Adapter_Pdo_Mysql
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2013 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractPdoAdapter extends AbstractAdapter {


	/**
	 * Adapter type
	 *
	 * @var string
	 */
	protected $_adapter;
	
	
	/**
	 * Table name when it is part of the backend configuration
	 * 
	 * @var $_table string
	 */
	protected $_table;

	
	protected $_operators = array(Condition::OPERATOR_GTHAN => '>'
								, Condition::OPERATOR_LTHAN => '<'
								, Condition::OPERATOR_EQUAL => '='
								, Condition::OPERATOR_DIFF => '!='
								 );

	/**
	 * Database name
	 * @var string
	 */
	protected $_database;	
	
	
	// test dataobject
	protected $_do = array();
	
	
	/**
	 * Current class being queried
	 * @var string
	 */
	protected $_class;
	
	
	/**
	 * Array of joined tables 
	 * @var array
	 */
	protected $_alreadyJoined = array();
	
	
	/**
	 * Current or latest select query
	 * @var \Zend_Db_Select
	 */
	protected $_select;
	
	
	/**
	 * Instanciate a PDO-based backend from a t41\Backend\BackendUri object 
	 *
	 * @param t41\Backend\BackendUri $uri
	 * @param string $alias
	 * @throws t41\Backend\Exception
	 */
	public function __construct(Backend\BackendUri $uri, $alias = null)
	{
		parent::__construct($uri, $alias);
		
		$url = explode('/', $uri->getUrl());
		
		if (isset($url[0])) {
			$this->_database = $url[0];
		} else {
			throw new Exception('MISSING_DBNAME_PARAM');
		}
		
		if (isset($url[1])) {
			$this->_table = $url[1];
		}
	}
	
	
	protected function _connect()
	{
		if (! $this->_ressource) {
			try {
				/* @var $this->_ressource Zend_Db_Adapter_Pdo */
				$this->_ressource = \Zend_Db::factory($this->_adapter, array(
										    								 'host'     => $this->_uri->getHost()
														    				,'username' => $this->_uri->getUsername()
														    				,'password' => $this->_uri->getPassword()
														    				,'dbname'   => $this->_database
																			,'options'	=> array(\PDO::ATTR_PERSISTENT) 
																		   )
													);
			} catch (\Zend_Db_Exception $e) {
				throw new Exception($e->getMessage());
			}		
		}
	}
	
	
	/**
	 * execute a query on the adapter ressource
	 * @param string $query
	 * @return array
	 */
	public function query($query)
	{
	    $this->_connect();
    	$array = array();
	    try {
    	    $res = $this->_ressource->query($query);
	        if (preg_match('/^SELECT/i', trim($query))) {
	            foreach ($res as $row) {
    	            $array[] = $row;
	            }
	       }
	    } catch (\Exception $e) {
	        throw new Exception("Query failed: " . $e->getMessage() . $e->getTraceAsString());   
	    }
	    return $array;
	}
	
	
	/**
	 * Save new set of data from a DataObject object using INSERT 
	 *
	 * @param t41\ObjectModel\DataObject $do
	 * @return boolean
	 * @throws t41\Backend\Exception
	 */
	public function create(ObjectModel\DataObject $do)
	{
		$table = $this->_getTableFromClass($do->getClass());
		
		if (! $table) {
			throw new Exception('MISSING_DBTABLE_PARAM');
		}
		
		// Look for unsaved object in properties and save them
		if (self::$recursionSave === true) {
			try {
				$res = $this->_saveNewObjects($do);
			} catch (\Exception $e) {
				throw new Exception("Error Creating recursive record in $table : " . $e->getMessage());
			}
		}
			
		// get a valid data array passing mapper if any
		$recordSet = $this->_mapper ? $do->map($this->_mapper, $this) : $do->toArray($this);

		if ($do->getUri()) { // User-defined primary key (since uri can't be an ObjectUri instance
			$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($do->getUri()->getClass()) : Backend::DEFAULT_PKEY;
			$recordSet['data'][$pkey] = $do->getUri();
		}
		
		$this->_setLastQuery('insert', $recordSet['data']);
		
		
		try {
			$this->_connect();
			$this->_ressource->insert($table, $recordSet['data']);
			$do->resetChangedState();
				
		} catch (\Exception $e) {
			if (true) {
				throw new Exception("Error Creating Record in $table : " . $e->getMessage());
			} else {
				return false;
			}
			
			return true;
		}
		
		// Build new object URI
		
		// @todo provide support for primary keys that are not generated by DB (not AUTO INCREMENTED INTEGER)
		$id = $do->getUri() ? $do->getUri() : $this->_ressource->lastInsertId();
		$uri = $table . '/' . $id;
		$uri = new ObjectModel\ObjectUri($uri, $this->getUri());

		// inject new ObjectUri object in data object
		$do->setUri($uri);

		/* get collection-handling properties (if any) and process them */
		foreach ($do->getProperties() as $property) {
			if (! $property instanceof Property\CollectionProperty) {
				continue;
			}
			$collection = $property->getValue();
			/* @var $member t41\ObjectModel\BaseObject */
			foreach ($collection->getMembers() as $member) {
				// @todo check that keyprop is set before
				if ($property->getParameter('keyprop')) {
					if (($prop = $member->getProperty($property->getParameter('keyprop'))) !== false) {
						$prop->setValue($uri);
					} else {
						throw new Exception(sprintf("member of '%s' class missing '%s' property"
											, $collection->getClass(), $property->getParameter('keyprop')));
					}
					$member->save();
				}
			}
		}
		
		return true;
	}
	
	
	/**
	 * Populate the given data object
	 *  
	 * @param t41\ObjectModel\DataObject $do data object instance
	 * @return boolean
	 */
	public function read(ObjectModel\DataObject $do, $data = null) 
	{
		if (! $do->getUri() instanceof ObjectUri) {
			throw new Exception('MISSING_URI_IN_DATAOBJECT');
		}
		
		if (is_array($data) && count($data) > 0) {
		    /* populate data object */
		    $do->populate($data, $this->_mapper);
		    $do->resetChangedState();
		    return true;		    
		}
		
		// get table to use
		$table = $this->_getTableFromUri($do->getUri());
				
		
		if (! $table) {
			\Zend_Debug::dump($do->getUri());
			throw new Exception('MISSING_DBTABLE_PARAM');
		}
		
		// primary key is either part of the mapper configuration or 'id'
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($do->getUri()->getClass()) : Backend::DEFAULT_PKEY;
		$this->_connect();
		
		// get data from backend
		$select = $this->_ressource->select()
								   ->from($table, $this->_getColumns($do))
								   ->limit(1);
		
		/* add clause for primary key(s) */
		foreach ($this->_preparePrimaryKeyClauses($do) as $key => $val) {
			$select->where("$key = ?", $val);
		}
		try {
			$data = $this->_ressource->fetchRow($select);
		} catch (\Exception $e) {
			echo $e->getMessage();
			\Zend_Debug::dump($e->getTrace());
			die;
		}
		
		if (empty($data)) {
			$do->resetUri();
			return false;
		}
		
		/* complete url part of the object uri */
		$do->getUri()->setUrl($table . '/' . $do->getUri()->getIdentifier());
		
		/* populate data object */
		$do->populate($data, $this->_mapper);
		$do->resetChangedState();
		
		return true;
	}
	
	
	/**
	 * Query and return a blob column
	 * @param ObjectModel\DataObject $do
	 * @param Property\AbstractProperty $property
	 * @throws Exception
	 * @return binary
	 */
	public function loadBlob(ObjectModel\DataObject $do, Property\AbstractProperty $property)
	{
		// get table to use
		$table = $this->_getTableFromUri($do->getUri());
		
		if (! $table) {
			throw new Exception('MISSING_DBTABLE_PARAM');
		}
		
		$column = $this->_mapper ? $this->_mapper->propertyToDatastoreName($do->getClass(), $property->getId()) : $property->getId();

		// primary key is either part of the mapper configuration or 'id'
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($do->getUri()->getClass()) : Backend::DEFAULT_PKEY;
		$this->_connect();
		
		// get data from backend
		// @todo Handle complex pkeys (via mapper definition)
		$select = $this->_ressource->select()
								     ->from($table,$column)
								       ->where("$pkey = ?", $do->getUri()->getIdentifier())
										 ->limit(1);
		
		return $this->_ressource->fetchOne($select);
	}
	
	
	
	/**
	 * Update record data in the backend with passed data object properties values 
	 *
	 * @param t41\ObjectModel\DataObject $do
	 * @return boolean
	 */
	public function update(ObjectModel\DataObject $do)
	{
		$res = $ures = true;
		$uri = $do->getUri();

		// get table to use
		$table = $this->_getTableFromUri($uri);
				
		if (! $table) {
			throw new Exception('MISSING_DBTABLE_PARAM');
		}

		// Look for unsaved object in properties and save them
		$this->_saveNewObjects($do);
		
		// Properties mapping (to array)
		// @todo check that property::hasChanged is used everywhere
		$data = $this->_mapper ? $do->map($this->_mapper, $this) : $data = $do->toArray($this, true);

		// @todo implement multi-columns pkey
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($uri->getClass()) : 'id';
		
		$this->_setLastQuery('update', $data, array('id' => $uri->getIdentifier(), 'table' => $table));
		
		try {
			$this->_connect();
			//$this->_ressource->beginTransaction();
			
			if (count($data['data']) > 0) {
				// don't use return statut of update() because if record isn't changed, value is false
				$where = '';
				foreach ($this->_preparePrimaryKeyClauses($do) as $key => $val) {
					$where .= $this->_ressource->quoteInto("$key = ?", $val);
				}
				
				$ures = $this->_ressource->update($table, $data['data'], $where);
			}
			
			// Reset properties changed state before saving collections to avoid recursion
			$do->resetChangedState();
				
			if (count($data['collections']) > 0) {
				/* get collection handling properties (if any) and process them */
				foreach ($data['collections'] as $collection) {
					$res = $res && $collection->getValue()->save();
				}
			}
			

//			$this->_ressource->commit();
			
		} catch (\Exception $e) {
			
	//		$this->_ressource->rollback();
			$this->_setLastQuery('update', $data, array('table' => $table, 'context' => $e->getMessage()));
			return false;

		}
		
		return $res;
	}
	
	
	/**
	 * Delete record in backend 
	 * 
	 * @param t41\ObjectModel\DataObject $do
	 * @return boolean
	 */
	public function delete(ObjectModel\DataObject $do)
	{	
		$uri = $do->getUri();
		
		if (! $uri) return false;
		
		// get table to use
		$table = $this->_getTableFromUri($uri);
		
		if (! $table) {
			throw new Exception('MISSING_DBTABLE_PARAM');
		}
		
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($uri->getClass()) : Backend::DEFAULT_PKEY;
		try {
			$this->_connect();
			$res = $this->_ressource->delete($table, $this->_ressource->quoteInto("$pkey = ?", $uri->getIdentifier()));
			$this->_setLastQuery('delete', null, array('table' => $table));
				
		} catch (\Exception $e) {
			$this->_setLastQuery('delete', null, array('table' => $table, 'context' => $e->getMessage()));
			return false;
		}
		
		return (bool) $res;
	}
	
	
	/**
	 * Returns an array of objects queried from the given t41_Object_Collection instance parameters
	 * 
	 * The given collection is populated if it comes empty of members.
	 * 
	 * In any other case, this method doesn't directly populate the collection. This action is under the responsability of 
	 * the caller. For example, the t41_Object_Collection::find() method takes care of it.
	 * 
	 * @param t41\ObjectModel\Collection $collection
	 * @param boolean|array $returnCount true = counting, array = stats on listed properties
	 * @param string $subOp complex operation like SUM or AVG
	 * @return array
	 */
	public function find(ObjectModel\Collection $collection, $returnCount = false, $subOp = null)
	{
		$this->_class = $class = $collection->getDataObject()->getClass();
		$table = $this->_getTableFromClass($class);
		
		if (! $table) {
			throw new Exception('MISSING_DBTABLE_PARAM');
		}
		
		// primary key is either part of the mapper configuration or 'id'
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : \t41\Backend::DEFAULT_PKEY;
		
		if (is_array($pkey)) {
			$composite = array();
			
			/* @var $obj t41\Backend\Key */
			foreach ($pkey as $obj) {
				$composite[] = sprintf('TRIM(%s)', $table . '.' . $obj->getName());
				$composite[] = Backend\Mapper::VALUES_SEPARATOR;
			}
			$pkey = sprintf("CONCAT(%s) AS %s", implode(',', $composite), Backend::DEFAULT_PKEY);
		} else {
			$pkey = $table . '.' . $pkey;
		}
		
		$this->_connect();
		
		/* @var $select \Zend_Db_Select */
		$this->_select = $this->_ressource->select();
		
		// detect if query is of stat-kind
		if ($returnCount) {
			switch ($subOp) {
				
				case ObjectModel::CALC_SUM:
					$expressions = array();
					foreach ($returnCount as $propKey => $property) {
						$prop = $this->_mapper ? $this->_mapper->propertyToDatastoreName($class, $propKey) : $propKey;
						$expressions[] = sprintf('SUM(%s.%s)', $table, $prop);
					}
					$subOpExpr = implode('+', $expressions);
					break;
					
				case ObjectModel::CALC_AVG:
					$subOpExpr = sprintf('AVG(%s)', $returnCount);
					break;
										
				default:
					$subOpExpr = 'COUNT(*)';
					break;
			}
			$this->_select->from($table, new \Zend_Db_Expr($subOpExpr . " AS " . \t41\Backend::MAX_ROWS_IDENTIFIER));
		} else {
			$this->_select->distinct();
			$this->_select->from($table, $pkey);
		}
		
		$this->_alreadyJoined = array();

		/* @var $condition t41\Backend\Condition */
		foreach ($collection->getConditions() as $conditionArray) {
			
			// combo conditions
			if ($conditionArray[0] instanceof Condition\Combo) {
				
				$statement = array();
				
				foreach ($conditionArray[0]->getConditions() as $condition) {
					$statement[] = $this->_parseCondition($condition[0], $this->_select, $table);
				}
				
				$statement = implode(' OR ', $statement);
				
				switch ($conditionArray[1]) {
				
					case Condition::MODE_OR:
						$this->_select->orWhere($statement);
						break;
							
					case Condition::MODE_AND:
					default:
						$this->_select->where($statement);
						break;
				}
				
				continue;
			}
			
			// optional table where the column may be
			$jtable = '';
			
			// condition object is in the first key
			$condition = $conditionArray[0];

			/* does condition contain another condition object ? */
			if ($condition->isRecursive()) {

				while ($condition->isRecursive()) {

					$property = $condition->getProperty();
					$parent	  = $property->getParent() ? $property->getParent()->getId() : $table;
					$condition = $condition->getCondition();
						
					if ($jtable) {
						$parentTable = $jtable;
					} else if ($parent) {
						$parentTable = $this->_mapper ? $this->_mapper->getDatastore($parent) : $parent;
					} else {
						$parentTable = $table;
					}
						
					$jtable = $this->_mapper ? $this->_mapper->getDatastore($property->getParameter('instanceof')) : $this->_getTableFromClass($property->getParameter('instanceof'));
						
					/* column name in left table */
					$jlkey  = $this->_mapper ? $this->_mapper->propertyToDatastoreName($class, $property->getId()) : $property->getId();
						
					$uniqext = $jtable . '__joined_for__' . $jlkey;
					if (in_array($uniqext, $this->_alreadyJoined)) {
						$class = $property->getParameter('instanceof');
						$jtable = $uniqext;
						continue;
					}
						
					/* pkey name in joined table */
					$jpkey  = $this->_mapper ? $this->_mapper->getPrimaryKey($property->getParameter('instanceof')) : Backend::DEFAULT_PKEY;
					
					$join = sprintf("%s.%s = %s.%s", $parentTable, $jlkey, $uniqext, $jpkey);
					$this->_select->joinLeft($jtable . " AS $uniqext", $join, array());
					$this->_alreadyJoined[$jtable] = $uniqext; //$jtable;
					$jtable = $uniqext;
					$class = $property->getParameter('instanceof');
				}
			}
				
			$property = $condition->getProperty();
			
			if ($property instanceof Property\ObjectProperty) {
				
				// no join if object is stored in a different backend !
				// @todo improve this part 
				if (ObjectModel::getObjectBackend($property->getParameter('instanceof'))->getAlias() != $this->_uri->getAlias()) {
					$clauses = $condition->getClauses();
					if ($clauses[0]['value'] != Condition::NO_VALUE) {
						$clauses[0]['operator'] = Condition::OPERATOR_ENDSWITH | Condition::OPERATOR_EQUAL;
						$condition->setClauses($clauses);
					}
					$field = $this->_mapper ? $this->_mapper->propertyToDatastoreName($this->_class, $property->getId()) : $property->getId();
				} else {
					// which table to join with ? (in case of condition is last element of a recursion)
					$jtable2 = $jtable ? $jtable : $table;
				
					$jtable = $this->_mapper ? $this->_mapper->getDatastore($property->getParameter('instanceof')) : $this->_getTableFromClass($property->getParameter('instanceof'));
				
					$leftkey  = $this->_mapper ? $this->_mapper->propertyToDatastoreName($class, $property->getId()) : $property->getId();
					$field = $rightkey  = $this->_mapper ? $this->_mapper->getPrimaryKey($property->getParameter('instanceof')) : Backend::DEFAULT_PKEY;

					$uniqext = $jtable . '__joined_for__' . $leftkey;
					if (! in_array($uniqext, $this->_alreadyJoined)) {
						$join = sprintf("%s.%s = %s.%s", $jtable2, $leftkey, $uniqext, is_array($rightkey) ? $rightkey[0] : $rightkey);
						$this->_select->joinLeft($jtable . " AS $uniqext", $join, array());
						$this->_alreadyJoined[$jtable] = $uniqext;
					}
					$jtable = $uniqext;
				}
			} else if ($property instanceof Property\CollectionProperty) {
				// handling of conditions based on collection limited to withMembers() and withoutMembers()
				$leftkey = $property->getParameter('keyprop');
				$field = $property->getId();
				$subSelect = $this->_ressource->select();
				$subseltbl = $this->_mapper ? $this->_mapper->getDatastore($property->getParameter('instanceof')) : $this->_getTableFromClass($property->getParameter('instanceof'));
				$subSelect->from($subseltbl, new \Zend_Db_Expr(sprintf("COUNT(%s)", $leftkey)));
				$join = sprintf("%s.%s = %s", $subseltbl, $leftkey, $pkey);
				$subSelect->where($join);
				
				$statement = $this->_buildConditionStatement(new \Zend_Db_Expr(sprintf("(%s)", $subSelect)), $condition->getClauses(), $conditionArray[1]);
				$this->_select->where($statement);
				continue;
			} else {
				$field = $property->getId();
				if ($this->_mapper) {
					$field = $this->_mapper->propertyToDatastoreName($class, $field);
				}
			}
			
			/* convert identifier tag to the valid primary key */
			if ($field == ObjectUri::IDENTIFIER) {
				// @todo handle multiple keys from mapper
				$field = $table . '.';
				$key = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : Backend::DEFAULT_PKEY;
				$field .= is_array($key) ? $key[0] : $key;
			}
			
			/* if a join was performed, prefix current field with table name */
			else if ($jtable) {
				if (array_key_exists($jtable, $this->_alreadyJoined)) {
					$field = $this->_alreadyJoined[$jtable] . '.' . $field;
				} else {
					$tmp = $jtable . '.';
					$tmp .= is_array($field) ? $field[0] : $field;
					$field = $tmp;
				}			
			} else {
				if (array_key_exists($table, $this->_alreadyJoined)) {
					$field = $this->_alreadyJoined[$table] . '.' . $field;
				} else {
					$field = $table . '.' . $field;
				}
			}

			if ($field instanceof Key) {
				$field = $table . '.' . $field->getName();
			}

			// protect DateProperty() with setted timepart parameter from misuse
			if ($property instanceof DateProperty && $property->getParameter('timepart') == true) {
				$field = "DATE($field)";
			}
			
			$statement = $this->_buildConditionStatement($field, $condition->getClauses(), $conditionArray[1]);

			switch ($conditionArray[1]) {
				
				case Condition::MODE_OR:
					$this->_select->orWhere($statement);
					break;
					
				case Condition::MODE_AND:
				default:
					$this->_select->where($statement);
					break;
			}
		}
		
		// Adjust query based on returnCount
		if ($returnCount) {
			if (is_array($returnCount)) {
				
				if ($subOp) {
					
				} else {
					// return count on grouped columns
					foreach ($returnCount as $key => $property) {
						$fieldmodifier = null;
						if ($this->_mapper) {
							$class = $property->getParent() ? $property->getParent()->getId() : $collection->getDataObject()->getClass();
							$field = $this->_mapper->propertyToDatastoreName($class, $property->getId());
						} else {
							$field = $property->getId();
						}
					
						if ($property instanceof ObjectProperty) {
							// join with $key if necessary
							if (strstr($key, '.') !== false) {
								$leftPart = substr($key, 0, strpos($key,'.'));
								$intermediateProp = $collection->getDataObject()->getProperty($leftPart);
								$fieldmodifier = $this->_join($intermediateProp, $table) . '.' . $field;
							}
						}
					
						// limit date grouping to date part, omitting possible hour part
						if ($property instanceof DateProperty) {
							$fieldmodifier = "DATE($field)";
						}
					
						$this->_select->group($fieldmodifier ? $fieldmodifier : $field);
						$this->_select->columns(array($field => $fieldmodifier ? $fieldmodifier : $field));
					}
				}
			} else {
				$this->_select->reset('group');
			}
		} else {
			$this->_select->limit($collection->getBoundaryBatch() != - 1 ? $collection->getBoundaryBatch() : null, $collection->getBoundaryOffset());
		
		/**
		 * Sorting part
		 */
		foreach ($collection->getSortings() as $sorting) {
			
		    $slUniqext = $slTable = null;
		    
			// Specific cases first
			// @todo find a better way to sort on meta properties 
			if ($sorting[0]->getId() == ObjectUri::IDENTIFIER || $sorting[0] instanceof MetaProperty) {
				$id = Backend::DEFAULT_PKEY;
				if ($sorting[1] != 'ASC' && $sorting[1] != 'DESC') continue;
				$this->_select->order(new \Zend_Db_Expr($table . '.' . $id . ' ' . $sorting[1]));
				continue;
			} else if ($sorting[0] instanceof Property\CollectionProperty) {
				// handling of conditions based on collection limited to withMembers() and withoutMembers()
				$leftkey = $sorting[0]->getParameter('keyprop');
				//$field = $property->getId();
				$subSelect = $this->_ressource->select();
				$subseltbl = $this->_mapper ? $this->_mapper->getDatastore($sorting[0]->getParameter('instanceof')) : $this->_getTableFromClass($sorting[0]->getParameter('instanceof'));
				$subSelect->from($subseltbl, new \Zend_Db_Expr(sprintf("COUNT(%s)", $leftkey)));
				$join = sprintf("%s.%s = %s", $subseltbl, $leftkey, $pkey);
				$subSelect->where($join);
				
				// $statement = $this->_buildConditionStatement(new \Zend_Db_Expr(sprintf("(%s)", $subSelect)), $condition->getClauses(), $conditionArray[1]);
				$this->_select->order(new \Zend_Db_Expr('(' . $subSelect->__toString() . ') ' . $sorting[1]));
				continue;
			} else if ($sorting[0] instanceof Property\ObjectProperty) {
			    
			    // find which property to sort by
			    if ($sorting[0]->getParameter('sorting')) {
			        $sprops = array_keys($sorting[0]->getParameter('sorting'));
			    } else {
    				// try to sort with properties used to display value
    	   			if (substr($sorting[0]->getParameter('display'), 0, 1) == '[') {
    					// @todo extract elements of pattern to order from them ?
    					$sprops = array('id');
    				} else {
    					$sprops = explode(',', $sorting[0]->getParameter('display'));
    				}
			    }

			    
			    // sorting property belongs to a second-level join
			    if ($sorting[0]->getParent()->getClass() != $collection->getClass()) {
			        $leftkey = 'commande'; //$this->_mapper ? $this->_mapper->propertyToDatastoreName($collection->getDataObject()->getClass(), $sorting[0]->getParent()getId()) : $sorting[0]->getId();
			        $class = $sorting[0]->getParent()->getClass();
			        $stable = $this->_getTableFromClass($class);
			        $sbackend = ObjectModel::getObjectBackend($class);
			        // Property to sort from is in a different backend from current one
			        if ($sbackend->getAlias() != $this->getAlias()) {
			            // We presume that the current backend is allowed to connect to the remote one
			            // Should we raise an exception instead ?
			            $stable = $sbackend->getUri()->getDatabase() . '.' . $stable;
			        }
			        $field = $sorting[0]->getId();
			        
			        $rightkey = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : Backend::DEFAULT_PKEY;
			        $uniqext = $stable . '__joined_for__' . $leftkey;
			        if (! in_array($uniqext, $this->_alreadyJoined)) {
			            if (is_array($rightkey)) {
			                foreach ($rightkey as $rightkeyObj) {
			                    $join = sprintf("%s.%s = %s.%s", $table, $leftkey, $uniqext, $rightkeyObj->getName());
			                }
			            } else {
			                $join = sprintf("%s.%s = %s.%s", $table, $leftkey, $uniqext, $rightkey);
			            }
			            $this->_select->joinLeft("$stable AS $uniqext", $join, array());
			            $this->_alreadyJoined[$stable] = $uniqext;
			        }
			        $slTable = $this->_getTableFromClass($sorting[0]->getParameter('instanceof'));
			        $slUniqext = $uniqext;
			    }

			    
			    $leftkey = $this->_mapper ? $this->_mapper->propertyToDatastoreName($collection->getDataObject()->getClass(), $sorting[0]->getId()) : $sorting[0]->getId();
				$class = $sorting[0]->getParameter('instanceof');
				$stable = isset($slTable) ? $slTable : $this->_getTableFromClass($class);
				$sbackend = ObjectModel::getObjectBackend($class);
				// Property to sort from is in a different backend from current one
				if ($sbackend->getAlias() != $this->getAlias()) {
					// We presume that the current backend is allowed to connect to the remote one
					// Should we raise an exception instead ?
					$stable = $sbackend->getUri()->getDatabase() . '.' . $stable;
				}
				$field = $sorting[0]->getId();

				$rightkey = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : Backend::DEFAULT_PKEY;
				$uniqext = $stable . '__joined_for__' . $leftkey;
				if (! in_array($uniqext, $this->_alreadyJoined)) {
					if (is_array($rightkey)) {
						foreach ($rightkey as $rightkeyObj) {
							$join = sprintf("%s.%s = %s.%s", $table, $leftkey, $uniqext, $rightkeyObj->getName());
						}
					} else {
						$join = sprintf("%s.%s = %s.%s", isset($slUniqext) ? $slUniqext : $table, $leftkey, $uniqext, $rightkey);
					}
					$this->_select->joinLeft("$stable AS $uniqext", $join, array());
					$this->_alreadyJoined[$stable] = $uniqext;
				}
					
				foreach ($sprops as $sprop) {
					if ($this->_mapper) {
						$sfield = $this->_mapper->propertyToDatastoreName($class, $sprop);
					} else {
						$sfield = $sprop;
					}
							
					$sortingExpr = $this->_alreadyJoined[$stable] . '.' . $sfield;
						
					if (isset($sorting[2]) && !empty($sorting[2])) {
						$sortingExpr = sprintf('%s(%s)', $sorting[2], $sortingExpr);
					}
					$this->_select->order(new \Zend_Db_Expr($sortingExpr . ' ' . $sorting[1]));
				}
				continue;
			}
			
			// default sorting on a different table
			$class = $sorting[0]->getParent() ? $sorting[0]->getParent()->getClass() : $collection->getDataObject()->getClass();
			$stable = $this->_getTableFromClass($class);
			
			if ($this->_mapper) {
				$sfield = $this->_mapper->propertyToDatastoreName($class, $sorting[0]->getId());
			} else {
				$field = $sorting[0];
				$sfield = $field->getId();
			}
			
			// add a left join if the sorting field belongs to a table not yet part of the query
			if ($stable != $table) {
				// get the property id from the class name
				$tfield = isset($sorting[3]) ? $sorting[3] : $collection->getDataObject()->getObjectPropertyId($class);
				
				$leftkey = $this->_mapper ? $this->_mapper->propertyToDatastoreName($class, $tfield) : $tfield;
				$rightkey = $this->_mapper ? $this->_mapper->getPrimaryKey($field->getParameter('instanceof')) : Backend::DEFAULT_PKEY;
				
				$uniqext = $stable . '__joined_for__' . $leftkey;
				if (! array_key_exists($stable, $this->_alreadyJoined)) {
				    $join = sprintf("%s.%s = %s.%s", $table, $leftkey, $uniqext, $rightkey);
				    try {
				        $this->_select->joinLeft("$stable AS $uniqext", $join, array());
				    } catch (\Exception $e) {
				        // silence exception
				    }
				    $this->_alreadyJoined[$stable] = $uniqext;
				}
				
				$sortingExpr = $this->_alreadyJoined[$stable] . '.' . $sfield;
			} else {
				$sortingExpr = $stable . '.' . $sfield;
				}
				if (isset($sorting[2]) && !empty($sorting[2])) {
					$sortingExpr = sprintf('%s(%s)', $sorting[2], $sortingExpr);
				}
				
				if (! $sorting[0] instanceof DateProperty) {
				    $sortingExpr = new \Zend_Db_Expr("TRIM($sortingExpr)");
				}
				$this->_select->order($sortingExpr . ' ' . $sorting[1]);
			}
		}
		
		$result = array();
		$context = array('table' => $table);
		
		try {
		    if (true && $returnCount == false) {
		        $this->_select->columns($this->_getColumns($collection->getDataObject()));
		    }
			$result = $this->_ressource->fetchAll($this->_select);
		} catch (\Zend_Db_Exception $e) {
			$context['error'] = $e->getMessage();
			$this->_setLastQuery($this->_select->__toString(), $this->_select->getPart('where'), $context);
			return false;
		}
		
		$this->_setLastQuery($this->_select->__toString(), $this->_select->getPart('where'), $context);
		
		if ($returnCount !== false) {
			return is_array($returnCount) ? $result : $result[0][Backend::MAX_ROWS_IDENTIFIER];
		}
		
		// convert array of primary keys to strings
		foreach ($result as $key => $val) {
		//	$result[$key] = implode(Backend\Mapper::VALUES_SEPARATOR, $val);
		}
		
		/* prepare base of object uri */
		$uri = new ObjectModel\ObjectUri();
		$uri->setBackendUri($this->_uri);
		$uri->setClass($collection->getDataObject()->getClass());
		$uri->setUrl($this->_database . '/' . $table . '/');
		
		return $collection->populate($result, $uri);
		//return $this->_populateCollection($result, $collection, $uri);
	}
	
	/**
	 * Return the latest SQL Select
	 * @return string
	 */
	public function getSql()
	{
	    return $this->_select ? $this->_select->__toString() : false;
	}
	
	public function transactionStart($key = null)
	{
		$this->_connect();
		if ($this->_ressource->beginTransaction()) {
			$this->_transaction = $key ?? true;
			$this->_setLastQuery('starting transaction');
			return true;
		} else {
		    $this->_setLastQuery('failed starting transaction');
		    return false;
		}
	}
	
	
	public function transactionCommit()
	{
		if ($this->transactionExists()) {
			$this->_transaction = false;
			try {
				$this->_ressource->commit();
				$this->_setLastQuery('commit transaction', 'OK');
				return true;
			} catch (\Exception $e) {
				$this->_ressource->rollBack();
				$this->_setLastQuery('commit transaction', $e->getMessage());
				return false;
			}
		}
	}
	
	
	/**
	 * Returns a condition statement string based on given field identifier and clause(s)
	 * 
	 * @param string	$field
	 * @param array		$clauses
	 * @param string 	$mode
	 * @return string
	 */
	protected function _buildConditionStatement($field, array $clauses, $mode = 'AND')
	{
		$statements = array();
		
		foreach ($clauses as $key => $clause) {
			$ops = $this->_matchOperator($clause['operator']);
			$_operators = $this->_operators;
			$fuzzy = false;
			
			// switch mode if requested 
			$mode = $clause['mode'];
			$value = $clause['value'];
			
			// IS NULL support
			if ($value == Condition::NO_VALUE) {
				if (array_sum($ops) == Condition::OPERATOR_EQUAL) {
					$statements[] = sprintf("%s IS NULL", $field);
				} else {
					$statements[] = sprintf("%s IS NOT NULL", $field);
				}
				continue;
			}
			
			/* if value is an t41\ObjectModel-derivated object, use its uri to get id value
			 * 
			 * @todo set a better way to check that a t41_Object_Uri contains a given t41_Backend_Uri/alias
			 *
			 * @var $value t41\ObjectModel\BaseObject
			 */
			if ($value instanceof ObjectModel\BaseObject || $value instanceof ObjectModel\DataObject) {
				if ($value->getUri() == null) {
					return false;
//					throw new Exception(array("OBJECT_HAS_NO_URI", get_class($value)));
				}
				
				if ($value->getUri()->getBackendUri() && $value->getUri()->getBackendUri()->getAlias() == $this->_uri->getAlias()) {
					$value = $value->getUri()->getIdentifier();
				
				} else {
					$value = $value->getUri();
				}
			} else if ($value instanceof ObjectModel\ObjectUri) {
				if ($value->getBackendUri()->getAlias() == $this->_uri->getAlias()) {
					$value = $value->getIdentifier();
				}
				/* in any other case, use uri's string representation as key */
			}
			
			if (is_array($value)) {
				$_operators[Backend\Condition::OPERATOR_EQUAL]	= 'IN';
				$_operators[Backend\Condition::OPERATOR_DIFF]	= 'NOT IN';
			
			} else {
				if (in_array(Backend\Condition::OPERATOR_BEGINSWITH, $ops)) {
					$value .= '%';
					$fuzzy = true;
				}
				if (in_array(Backend\Condition::OPERATOR_ENDSWITH, $ops)) {
					$value = '%' . $value;
					$fuzzy = true;
				}
			}
			
			if ($fuzzy) {
				$_operators[Backend\Condition::OPERATOR_EQUAL]	= 'LIKE';
				$_operators[Backend\Condition::OPERATOR_DIFF]	= 'NOT LIKE';
			}
		
			$operator = '';
		
			foreach ($ops as $op) {
				if (isset($_operators[$op])) {
					$operator .= $_operators[$op];
				}
			}
		
			$needle = is_array($value) ? '(?)' : '?';
			if (is_array($field)) { // $field contains primary keys
				$pkeyVals  = explode(Backend\Mapper::VALUES_SEPARATOR, $value);
				foreach ((array) $field as $fkey => $fpart) {
					if (! isset($pkeyVals[$fkey])) continue;
					$statements[] = $this->_ressource->quoteInto(sprintf("%s %s $needle", $fpart->getName(), $operator), $fpart->castValue($pkeyVals[$fkey]));
				}
			} else {
				$statements[] = $this->_ressource->quoteInto(sprintf("%s %s $needle", $field, $operator), $value);
			}
		}
		
		return implode(" $mode ", $statements);
	}
	
	
	protected function _parseCondition(Condition $condition, \Zend_Db_Select $select, $table)
	{
		$jtable = '';
		/* does condition contain another condition object ? */
		if ($condition->isRecursive()) {
			while ($condition->isRecursive()) {
				$property = $condition->getProperty();
				$parent	  = $property->getParent() ? $property->getParent()->getId() : $table;
				$condition = $condition->getCondition();
		
				if ($jtable) {
					$parentTable = $jtable;
				} else if ($parent) {
					$parentTable = $this->_mapper ? $this->_mapper->getDatastore($parent) : $parent;
				} else {
					$parentTable = $table;
				}

				$jtable = $this->_mapper ? $this->_mapper->getDatastore($property->getParameter('instanceof')) : $this->_getTableFromClass($property->getParameter('instanceof'));
					
				if (array_key_exists($jtable, (array) $this->_alreadyJoined)) {
					$class = $property->getParameter('instanceof');
					continue;
				}
				
				$uniqext = $jtable . '__joined_for__' . $parentTable;
					
				/* column name in left table */
				$jlkey  = $this->_mapper ? $this->_mapper->propertyToDatastoreName($class, $property->getId()) : $property->getId();
		
				/* pkey name in joined table */
				$jpkey  = $this->_mapper ? $this->_mapper->getPrimaryKey($property->getParameter('instanceof')) : Backend::DEFAULT_PKEY;
					
				$join = sprintf("%s.%s = %s.%s", $parentTable, $jlkey, $uniqext, $jpkey);
				$this->_select->joinLeft("$jtable AS $uniqext", $join, array());
				$this->_alreadyJoined[$jtable] = $uniqext; //$jtable;
				$class = $property->getParameter('instanceof');
			}
		}
		
		$property = $condition->getProperty();
			
		if ($property instanceof Property\ObjectProperty) {
		
			// which table to join with ? (in case of condition is last element of a recursion)
			$jtable2 = $jtable ? $jtable : $table;
		
			$jtable = $this->_mapper ? $this->_mapper->getDatastore($property->getParameter('instanceof')) : $this->_getTableFromClass($property->getParameter('instanceof'));
		
    	    if (! array_key_exists($jtable, $this->_alreadyJoined)) {
    			$leftkey  = $this->_mapper ? $this->_mapper->propertyToDatastoreName($class, $property->getId()) : $property->getId();
    			$field = $rightkey  = $this->_mapper ? $this->_mapper->getPrimaryKey($property->getParameter('instanceof')) : Backend::DEFAULT_PKEY;
    			$uniqext = $jtable . '__joined_for__' . $leftkey;
    				
    			$join = sprintf("%s.%s = %s.%s", $jtable2, $leftkey, $uniqext, $rightkey);
    			$select->joinLeft("$jtable AS $uniqext", $join, array());
    		
    			$this->_alreadyJoined[$jtable] = $uniqext; //$jtable;
    		}
		} else {
			$field = $property->getId();
			if ($this->_mapper) {
				$field = $this->_mapper->propertyToDatastoreName($property->getParent()->getClass(), $field);
			}
		}
		
		/* convert identifier tag to the valid primary key */
		if ($field == ObjectUri::IDENTIFIER) {
			// @todo handle multiple keys from mapper
			$field = $table . '.';
			$key = $this->_mapper ? $this->_mapper->getPrimaryKey($this->_class) : Backend::DEFAULT_PKEY;
			$field = is_array($key) ? $key[0] : $key;
		}
		
		/* if a join was performed, prefix current field with table name */
		// @todo refactor there and in find()
		if ($jtable) {
			$field = $this->_alreadyJoined[$jtable] . '.' . $field;
		} else if($table) {
			$field = $table . '.' . $field;
		}
		
		$statement = $this->_buildConditionStatement($field, $condition->getClauses(), 'OR'); //$conditionArray[1]);
		
		return $statement;
	}
	
	
	/**
	 * Perform the correct join from given object property and return the table alias
	 * @todo test and implement in various places in find() method
	 * @param Property\ObjectProperty $property
	 * @param string $table
	 * @return string
	 */
	protected function _join(Property\ObjectProperty $property, $table)
	{
		$join = array();
		
		$class = $property->getParameter('instanceof');
		$stable = $this->_getTableFromClass($class);
		$leftkey = $this->_mapper ? $this->_mapper->propertyToDatastoreName($property->getParent()->getDataObject()->getClass(), $property->getId()) : $property->getId();
		
		$uniqext = $stable . '__joined_for__' . $leftkey;
		
		if (! isset($this->_alreadyJoined[$stable])) {
			$sbackend = ObjectModel::getObjectBackend($class);
		
			if ($sbackend->getAlias() != $this->getAlias()) {
				// @todo raise and exception if backends are not of same type
				// We presume that the current backend is allowed to connect to the remote one
				// Should we raise an exception instead ?
				$stable = $sbackend->getUri()->getDatabase() . '.' . $stable;
			}
			$field = $property->getId();
		
			$rightkey = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : Backend::DEFAULT_PKEY;
			if (is_array($rightkey)) {
				foreach ($rightkey as $rightkeyObj) {
					// @todo fix left key that should be provided by mapper 
					$join[] = sprintf("%s.%s = %s.%s", $table, $leftkey, $uniqext, $rightkeyObj->getName());
				}
			} else {
				$join[] = sprintf("%s.%s = %s.%s", $table, $leftkey, $uniqext, $rightkey);
			}
			$this->_select->joinLeft("$stable AS $uniqext", implode(' AND ', $join), array());
			$this->_alreadyJoined[$stable] = $uniqext;
		}
		return $uniqext;
	}
}
