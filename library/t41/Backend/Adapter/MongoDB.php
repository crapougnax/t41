<?php
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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 856 $
 */


/** Required files */
require_once 't41/Backend/Adapter/Abstract.php';

/**
 * class providing all methods to use with MongoDB
 *
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class t41_Backend_Adapter_MongoDB extends t41_Backend_Adapter_Abstract {


	/**
	 * DB-binded connection
	 *
	 * @var MongoDB
	 */
	protected $_db;
	
	protected $_operators = array(t41_Condition::OPERATOR_GTHAN => '$gt'
								, t41_Condition::OPERATOR_LTHAN => '$lt'
								, t41_Condition::OPERATOR_EQUAL => '$in'
								, t41_Condition::OPERATOR_DIFF => '$nin'
								 );
								 	
	/**
	 * Database name
	 * @var string
	 */
	protected $_database;
	

	/**
	 * Collection name
	 * 
	 * database name as mongodb names it
	 * @var string
	 */
	protected $_collection;
	
	
	
	/**
	 * Instanciate a MongoDB backend from a t41_Backend_Uri object 
	 *
	 * @param t41_Backend_Uri $uri
	 * @param string $alias
	 * @throws t41_Backend_Exception
	 */
	public function __construct(t41_Backend_Uri $uri, $alias = null)
	{
		if (! extension_loaded('mongo')) {
			
			throw new t41_Backend_Exception(array("BACKEND_REQUIRED_EXT", 'mongo'));
		}

		parent::__construct($uri, $alias);
		
		$url = explode('/', $uri->getUrl());
		
		/* @todo do we need to force database declaration in backend ? */
		if (! empty($url[0])) {
			
			$this->_database = $url[0];
			
		} else {
			
			require_once 't41/Backend/Exception.php';			
			throw new t41_Backend_Exception('BACKEND_MISSING_DBNAME_PARAM');
		}
		
		if (! empty($url[1])) {
			
			$this->_collection = $url[1];
		}
		
		try {

			$this->_ressource = new Mongo($this->_uri->getHost());
			
		} catch (MongoException $e) {
			
			require_once 't41/Backend/Exception.php';
			throw new t41_Backend_Exception($e->getMessage());
			
		} catch (InvalidArgumentException $e) {
			
			require_once 't41/Backend/Exception.php';
			throw new t41_Backend_Exception($e->getMessage());			
		}
	}
	
	
	/**
	 * Save new set of data from a t41_Data_Object object using INSERT 
	 *
	 * @param t41_Data_Object $do
	 * @return boolean
	 * @throws t41_Backend_Exception
	 */
	public function create(t41_Data_Object $do)
	{
		// set database to use
		$this->_selectDatabase($do->getUri());
		
		// get collection to use, from mapper if available, else from data object
		$collection = ($this->_mapper instanceof t41_Backend_Mapper) ? $this->_mapper->getDatastore($do->getClass()) : $do->getClass();

		$collec = $this->_db->selectCollection($collection);
		
		// get a valid data array passing mapper if any
		if ($this->_mapper) {

			$recordSet = $do->map($this->_mapper, 'backend', $this->_uri->getAlias());
			
		} else {
			
			$recordSet = $do->toArray($this->_uri->getAlias());
		}
		
		/* @todo check wether... */
		try {

			$collec->insert($recordSet);
			
		} catch (Exception $e) {
			
			// @todo decide whether to throw an exception or just save last message in a property
			die($e->getMessage());
			return false;
		}
		
		// inject new t41_Object_Uri object in data object
		$uri = t41_Backend::PREFIX . $this->_uri->getAlias() . '/' . $this->_database 
		     . '/' . $collection . '/' . $recordSet['_id']->__toString();
		
		$uri = new t41_Object_Uri($uri, $this->_uri);
		$do->setUri($uri);
		
		return true;
	}
	
	
	
	/**
	 * Retourne l'objet lu depuis le backend
	 *  
	 * @param t41_Data_Object $do Data object to populate
	 * @return t41_Data_Object populated data object
	 */
	public function read(t41_Data_Object $do) 
	{
		$uri = $do->getUri();
		
		// set database to use
		$this->_selectDatabase($uri->getBackendUri());
		
		// get table to use, from mapper if available, else from data object
		$collection = ($this->_mapper instanceof t41_Backend_Mapper) ? $this->_mapper->getDatastore($uri->getClass()) : $uri->getClass();


		$collec = $this->_db->selectCollection($collection);
		
		// get data from backend
		$data = array('_id' => new MongoId($uri->getIdentifier()));
		$this->_setLastQuery('findOne', $data);
		$data = $collec->findOne($data);
		
		if (empty($data)) {
			
			return null;
		}
		
		/* complete url part of the object uri */
		$do->getUri()->setUrl($this->_database . '/' . $collection . '/' . $do->getUri()->getIdentifier());
		
		/* populate data object */
		$do->populate($data, $this->_mapper);
	}
	
	
	
	/**
	 * Enregistre les modifications de l'Objet correspondant au DataObject donné en paramètres
	 *
	 * @param t41_Data_Object $do
	 * @return mixed
	 */
	public function update(t41_Data_Object $do)
	{
		$uri = $do->getUri();
		
		// get table to use, from mapper if available, else from data object
		$table = ($this->_mapper instanceof t41_Backend_Mapper) ? $this->_mapper->getDatastore($uri->getClass()) : $do->getClass();
		
		// prepare data
		$data = $do->toArray();

		// Mapping et conversion de valeurs
		$data = $this->_mapper->objectToArray($data, $uri->getClass());
		
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($uri->getClass()) : 'id';
		
		return $this->_ressource->update($table, $data, $this->_ressource->quoteInto("$pkey = ?", $uri->getIdentifier()));
	}
	
	
	/**
	 * Efface la ligne la base identifié par l'Uri en paramètre
	 *
	 * @param t41_Data_Object $do
	 * @return mixed
	 */
	public function delete(t41_Data_Object $do)
	{		
		// on découpe l'url de l'URI afin d'obtenir les parties qui nous interesse.
		$uri = $do->getUri();
		
		// on trouve la base de donnée à utiliser
		$database = $this->_selectDatabase($uri);
		
		// on trouve la table à utiliser
		$table = $this->_selectTable($uri);
		
		//return $this->_ressource->delete($table, $table . "_id = " . $url['id']);
	}
	
	
	public function find(t41_Object_Collection $collection)
	{
		$class = $collection->getDataObject()->getClass();
		$mode  = $collection->getParameter('memberType');
		
		// set database to use
		$this->_selectDatabase($collection->getDataObject()->getUri());
		
		// get collection to use, from mapper if available, else from data object
		$collec = ($this->_mapper instanceof t41_Backend_Mapper) ? $this->_mapper->getDatastore($class) : $class;

		$collec = $this->_db->selectCollection($collec);

		// primary key is either part of the mapper configuration or 'id'
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : 'id';
		
		/* @var $select Zend_Db_Select */

		$conditions = array();
		
		/* @var $condition t41_Condition */
		foreach ($collection->getConditions() as $conditionArray) {
			
			$condition = $conditionArray[0];
			
			// map property to field
			if ($this->_mapper) {

				$field = $this->_mapper->propertyToDatastoreName($class, $condition->getProperty()->getId());
			
			} else {

				$field = $condition->getProperty()->getId();				
			}
			
			$conditions += $this->_buildConditionStatement($field, $condition->getClauses(), $conditions);
			
			
			switch ($conditionArray[1]) {
				
				case 'OR':
					//$select->orWhere($statement);
					break;
					
				case 'AND':
				default:
					//$select->where($statement);
					break;
			}
		}
		
		//Zend_Debug::dump($conditions);
		
		foreach ($collection->getSortings() as $sorting) {
			
			if ($this->_mapper) {

				$field = $this->_mapper->propertyToDatastoreName($class, $sorting[0]->getId());
			
			} else {

				$field = $sorting[0]->getId();				
			}
			
			//$select->order($field, $sorting[1]);
		}
		
//		$select->limit($collection->getBatchBoundary(), $collection->getOffsetBoundary());

		$ids = $collec->find($conditions, array('_id'));
		
		/* prepare base of object uri */
		$uri = new t41_Object_Uri();
		$uri->setBackendUri($this->_uri);
		$uri->setClass($collection->getDataObject()->getClass());
		$uri->setUrl($this->_database . '/');
		
		return $this->_populateCollection(iterator_to_array($ids), $collection, $uri);
	}
	
	
	public function returnsDistinct(t41_Object_Collection $collection, t41_Property_Abstract $property)
	{
		$class = $collection->getDataObject()->getClass();
		$mode  = $collection->getParameter('memberType');
		
		// set database to use
		$this->_selectDatabase();
		
		// get collection to use, from mapper if available, else from data object
		$collec = ($this->_mapper instanceof t41_Backend_Mapper) ? $this->_mapper->getDatastore($class) : $class;

		//$collec = $this->_db->selectCollection($collec);

		$conditions = array();
		
		/* @var $condition t41_Condition */
		foreach ($collection->getConditions() as $conditionArray) {
			
			$condition = $conditionArray[0];
			
			// map property to field
			if ($this->_mapper) {

				$field = $this->_mapper->propertyToDatastoreName($class, $condition->getProperty()->getId());
			
			} else {

				$field = $condition->getProperty()->getId();				
			}
			
			$conditions += $this->_buildConditionStatement($field, $condition->getClauses(), $conditions);
			
			
			switch ($conditionArray[1]) {
				
				case 'OR':
					//$select->orWhere($statement);
					break;
					
				case 'AND':
				default:
					//$select->where($statement);
					break;
			}
		}
		
		$params = array();
		$params['distinct'] = $collec;
		$params['key'] = $property->getId();
		$params['query'] = $conditions;
		
		$this->_setLastQuery('command', $params);
		$ids = $this->_db->command($params);

		/* @todo if property is an object, we should get all values from the list of ids */
		return isset($ids['values']) ? $ids['values'] : array();
	}
	
	
	/**
	 * Set database connection to use 
	 *
	 * @param t41_Backend_Uri $uri
	 * @return string
	 * @throws t41_Backend_Exception
	 */
	protected function _selectDatabase(t41_Backend_Uri $uri = null)
	{		
		if (! empty($this->_database)) {
			
			$this->_db = $this->_ressource->selectDB($this->_database);
			return $this->_database;
			
		} else {
			
			require_once 't41/Backend/Exception.php';
			throw new t41_Backend_Exception("Aucune base de donnée sélectionnée pour la requète");
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
	protected function _buildConditionStatement($field, array $clauses, $conditions)
	{
		if (! isset($conditions[$field])) $conditions[$field] = array();
		
		foreach ($clauses as $key => $clause) {
			
			$ops = $this->_matchOperator($clause['operator']);
			$_operators = $this->_operators;
			$value = $clause['value'];
			
			/* if value is an t41_Object_Model-derivated object, use its uri to get id value
			 * 
			 * @todo set a better way to check that a t41_Object_Uri contains a given t41_Backend_Uri/alias
			 *
			 * @var $value t41_Object_Model
			 */
			if ($value instanceof t41_Object_Model) {

				if ($value->getUri()->getBackendUri()->getAlias() == $this->_uri->getAlias()) {
					
					$value = $value->getUri()->getIdentifier();
				
				} else {
					
					$value = $value->getUri();
				}
			}
			
			if (is_array($value)) {
		
//				$_operators[t41_Condition::OPERATOR_EQUAL]	= 'IN';
//				$_operators[t41_Condition::OPERATOR_DIFF]	= 'NOT IN';
			
			} else {

				if (in_array(t41_Condition::OPERATOR_BEGINSWITH, $ops)) {
			
					$value .= '/';
				}
		
				if (in_array(t41_Condition::OPERATOR_ENDSWITH, $ops)) {
			
					$value = '/' . $value;
				}
			}
		
			foreach ($ops as $op) {
			
				if (isset($_operators[$op])) {
				
					$cval = is_numeric($value) ? (float) $value : $value;
					$cval = in_array($_operators[$op], array($this->_operators[t41_Condition::OPERATOR_EQUAL], $this->_operators[t41_Condition::OPERATOR_DIFF])) ? (array) $cval : $cval;
					$conditions[$field][$_operators[$op]] = $cval;
					
				}
			}
			
		}
		
		return $conditions;
	}
	
	
	protected function _populateCollection(array $ids, t41_Object_Collection $collection, t41_Object_Uri $uriBase)
	{
		foreach ($ids as $key => $val) {
			
			$ids[$key] = $key;
		}
		
		return parent::_populateCollection($ids, $collection, $uriBase);
	}
	
	
	protected function _setLastQuery($literal, $data = null, $context = null)
	{
		return parent::_setLastQuery($literal, $data, array('db' => $this->_database, 'collection' => $this->_collection));
	}
	
}