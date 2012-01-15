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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\Backend;
use t41\ObjectModel;
use t41\ObjectModel\Property;

/**
 * LDAP adapter class based on Zend_Ldap whenever possible...
 * 
 * @category   t41
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class LdapAdapter extends AdapterAbstract {


	/**
	 * Comparison operators array
	 * Empty here because LDAP deals with comparison its own way
	 * 
	 * @var array
	 */
	protected $_operators = array(Backend\Condition::OPERATOR_GTHAN => '>'
								, Backend\Condition::OPERATOR_LTHAN => '<'
								, Backend\Condition::OPERATOR_EQUAL => '='
								, Backend\Condition::OPERATOR_DIFF  => '=' // needs specific syntax (!(field=val))
								 );
	/**
	 * Base DN
	 * @var string
	 */
	protected $_baseDN;	
	
	
	/**
	 * Actual bound DN
	 * @var string
	 */
	protected $_currentDn;
	
	
	/**
	 * LDAP Connection ressource
	 * 
	 * @var Zend_Ldap
	 */
	protected $_ressource;
	
	
	/**
	 * Instanciate a PDO-based backend from a t41_Backend_Uri object 
	 *
	 * @param t41_Backend_Uri $uri
	 * @param string $alias
	 * @throws t41_Backend_Exception
	 */
	public function __construct(Backend\BackendUri $uri, $alias = null)
	{
		if (! extension_loaded('ldap')) {
			
			throw new Exception(array("BACKEND_REQUIRED_EXT", 'ldap'));
		}
		
		parent::__construct($uri, $alias);
		
		$this->_baseDN = $uri->getUrl();
	}
	
	
	/**
	 * Save new set of data from a t41_Data_Object object
	 *
	 * @param t41_Data_Object $do
	 * @return boolean
	 * @throws t41_Backend_Exception
	 */
	public function create(ObjectModel\DataObject $do)
	{
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($do->getClass()) : 'uid';
		$recordSet = $do->toArray($this->_uri->getAlias());
		
		// get a valid data array passing mapper if any
		if ($this->_mapper) {

			// get object class
			$objectClass = $this->_mapper->getDataclass($do->getClass());
			$subDn = $this->_mapper->getDatastore($do->getClass());
			
			$recordSet = $do->map($this->_mapper, $this->_uri->getAlias());
			
		} else {
		
			/*
			 * If no mapper is available, LDAP objectClass is supposed to be the same as PHP object class
			 * CN is a MD5 hash of recordSet 
			 */
			
			$objectClass = $do->getClass();
			$subDn = null;
		}
				
		$recordSet['data']['objectClass'] = $objectClass;

		try {
			
			if (! $this->_ressource) $this->_connect($subDn);
			
			// Define unique part of the record DN
			// If no value is specified, get a unique identifier
			$dn = sprintf('%s=%s', $pkey, isset($recordSet['data'][$pkey]) ? $recordSet['data'][$pkey] : $this->_getIdentifier($this->_mapper->getDatastore($do->getClass())));
		
			// Datastore value may contain supplemental path like 'ou=...'
			if (($ou = $this->_mapper->getDatastore($do->getClass())) != null) {
			
				$dn = $dn . ',' . $ou;
			}
		
			$dn .= ',' . $this->_baseDN;
			
			$this->_ressource->save($dn, $recordSet['data']);
			
		} catch (Exception $e) {
			
			// @todo decide wether throw an exception or just save last message in a property
			die($e->getMessage());
			return false;
		}
		
		$uri = new ObjectModel\ObjectUri();
		$do->setUri($this->getAlias() . '/' . $dn);
		
		/* get collection handling properties (if any) and process them */
		foreach ($do->getProperties() as $property) {
			
			if (! $property instanceof Property\CollectionProperty) {
				
				continue;
			}
			
			$collection = $property->getValue();
			
			var_dump($collection->getMembers());
			
			/* @var $member t41_Object_Model */
			foreach ($collection->getMembers() as $member) {

				
				$member->setProperty($property->getParameter('keyprop'), $uri);
				$member->save();
			}
		}
		
		return true;
	}
	
	
	
	
	
	/**
	 * Populate the given data object
	 *  
	 * @param t41_Data_Object $do data object instance
	 * @return boolean
	 */
	public function read(t41_Data_Object $do) 
	{
		$subDn = $this->_mapper ? $this->_mapper->getDatastore($do->getClass()) : null;

		// get data from backend
		try {
			if (! $this->_ressource) $this->_connect($subDn);
			//$data = $this->_ressource->getEntry($do->getUri()->getIdentifier());
			$data = $this->_ressource->search('(objectClass=*)', $do->getUri()->getIdentifier());
			
		} catch (Exception $e) {
			
			throw new Exception($e->getMessage);
		}
				
		if (empty($data)) {
			
			return false;
		}
		
		// Normalize array before mapping
		// Almost each record in a LDAP result array is an array
		$data = $this->_flattenArray($data);
		
		$do->populate($data, $this->_mapper);
		
		return true;
	}
	
	
	
	/**
	 * Update record data in the backend with passed data object properties values 
	 *
	 * @param t41_Data_Object $do
	 * @return boolean
	 */
	public function update(ObjectModel\DataObject $do)
	{
		// Properties mapping (to array)
		if ($this->_mapper) {
			
			$data = $do->map($this->_mapper, $do->getClass());
		
		} else {
			
			$data = $do->toArray();
		}

		$data = $this->_unflattenArray($data);
		
		return (bool) $this->_ressource->update($do->getUri()->getIdentifier(), $data);
	}
	
	
	/**
	 * Delete record in backend 
	 * 
	 * @param t41_Data_Object $do
	 * @return boolean
	 */
	public function delete(t41_Data_Object $do)
	{		
		// @todo add a try/catch block
		return (bool) $this->_ressource->delete($do->getUri()->getIdentifier());
	}
	
	
	/**
	 * Returns an array of objects queried from the given t41_Object_Collection instance parameters
	 * 
	 * The given collection is populated if it comes empty of members.
	 * 
	 * In any other case, this method doesn't directly populate the collection. This action is under the responsability of 
	 * the caller. For example, the t41_Object_Collection::find() method takes care of it.
	 * 
	 * @param t41_Object_Collection $collection
	 * @return array
	 */
	public function find(t41_Object_Collection $collection)
	{
		$class = $collection->getDataObject()->getClass();
		$filters = $sortings = array();
		$searchMode = '&';
		
		// primary key is either part of the mapper configuration or 'dn'
		$pkey = $this->_mapper ? $this->_mapper->getPrimaryKey($class) : 'dn';
		
		/* @var $condition t41_Condition */
		foreach ($collection->getConditions() as $conditionArray) {
			
			$condition = $conditionArray[0];

			/* does condition contain another condition object ? */
			if ($condition->isRecursive()) {
				
				// not supported with LDAP
				continue;
			}
				
			$property = $condition->getProperty();
					
			if ($property instanceof Property\ObjectProperty) {

				
			} else {
				
				$field = $property->getId();
				
				if ($this->_mapper) {

					$field = $this->_mapper->propertyToDatastoreName($class, $field);
				}
			}

			$filters[] = $this->_buildConditionStatement($field, $condition->getClauses());

			switch ($conditionArray[1]) {
				
				case 'OR':
					
//					$select->orWhere($statement);
					break;
					
				case 'AND':
				default:
//					$select->where($statement);
					break;
			}
		}
		
		foreach ($collection->getSortings() as $sorting) {
			
			if ($this->_mapper) {
			
				$class = $sorting[0]->getParent() ? $sorting[0]->getParent()->getId() : $collection->getDataObject()->getClass();
				$field = $this->_mapper->propertyToDatastoreName($class, $sorting[0]->getId());
			
			} else {

				$field = $sorting[0]->getId();
			}
			
			$sortings[] = $field;
		}

		$filter = implode($filters);
		
		if (count($sortings) > 0) {
			
			$filter .= sprintf('(sort=%s)', implode(',', $sortings));
		}
		
		if ($filter) $filter = sprintf('%s%s', $searchMode, $filter);
		
		$filter = $filter ? '(' . $filter . ')' : "(objectClass=*)";
		
		try {
			
			if ($this->_mapper) {
				
				$this->_connect($this->_mapper->getDatastore($collection->getDataObject()->getClass()));
				
			} else {
				
				$this->_connect();
			}
			
			/* @var $result Zend_Ldap_Collection */
/*			$result = $this->_ressource->search(  $filter
												, $this->_currentDn					// Base DN
												, null //Zend_Ldap::SEARCH_SCOPE_ONE		// Scope
												, null//array('sizeLimit' => $collection->getBoundaryBatch())
											   ); // query result
*/			
			$search = ldap_search($this->_ressource, $this->_currentDn, $filter);
			$result = ldap_get_entries($this->_ressource, $search); 
			
		} catch (Exception $e) {
			
			throw new Exception("LDAP Query error: " . $e->getMessage());
		}

		// first result is total records
		unset($result['count']);
		
		// populate array with relevant objects type
		$array = array();
		
		$uri = new ObjectModel\ObjectUri();
		$uri->setBackendUri($this->_uri);
		$uri->setClass($class);

		if ($collection->getParameter('memberType') != 'uri') {
			
			$do = new ObjectModel\DataObject($class);
		}
		
		foreach ($result as $entry) {
			
			$entry = $this->_flattenArray($entry, true);
			
			$uri->setUrl($this->_uri->getAlias() . '/' . $entry['dn']);
			
			switch ($collection->getParameter('memberType')) {

				case 'uri':
					$data = clone $uri;
					break;
					
				case 'data':
				default:
					$do->setUri(clone $uri);
					$do->populate($entry, $this->_mapper);
					$data = clone $do;
					break;
					
				case 'model':
					$do->setUri(clone $uri);
					$do->populate($entry, $this->_mapper);
					/* @var $obj t41_Object_Model */
					$data = new $class(null, null, clone $do);
					break;
			}
			
			$array[] = $data;
		}
		
		return $array;
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
			
			$prefix = $suffix = null;

			$ops = $this->_matchOperator($clause['operator']);
			
			$value = $clause['value'];
			
			if (in_array(Backend\Condition::OPERATOR_BEGINSWITH, $ops)) {
				
				$value .= '*';
				$filter = \Zend_Ldap_Filter::begins($field, $value);
			}
			
			if (in_array(Backend\Condition::OPERATOR_ENDSWITH, $ops)) {
				
				$value = '*' . $value;
				if ($filter instanceof \Zend_Ldap_Filter) {
					
					$filter = \Zend_Ldap_Filter::contains($field, $value);
				} else {
					
					$filter = \Zend_Ldap_Filter::ends($field, $value);
				}
			}

			
			if (in_array(Backend\Condition::OPERATOR_DIFF, $ops)) {
				
				$prefix = '(!';
				$suffix = ')';
			}
			
			
			/* if value is an t41_Object_Model-derivated object, use its uri to get id value
			 * 
			 * @todo set a better way to check that a t41_Object_Uri contains a given t41_Backend_Uri/alias
			 *
			 * @var $value t41_Object_Model
			 */
			if ($value instanceof ObjectModel\ObjectModel) {

					throw new Exception("LDAP Adapter doesn't support conditions which value is an object: " . $field);
			}
		
			$operator = '=';
		
			$statements[] = sprintf('%s(%s%s%s)%s', $prefix, $field, $operator, $value, $suffix);
		}
		
		return implode($statements);
	}
	
	
	/**
	 * Flatten an array returned by a LDAP server
	 * into a traditional one.
	 *  
	 * @param array $array
	 * @param boolean $searchResult
	 * @return array
	 */
	protected function _flattenArray(array $array1, $searchResult = false)
	{
		$array2 = array();
		
		if ($searchResult) {
			
			unset($array2['count']);
			
			foreach ($array1 as $key => $value) {
			
				if (is_numeric($key)) continue;
				
				if (is_array($value)) {
				
					$val = null;
					
					for ($i = 0 ; $i < $value['count'] ; $i++) {
						
						if ($val) $val .= "\n";
						$val .= $value[$i];
					}
					
				} else {
					
					$val = $value;
				}
					$array2[ strtolower($key) ] = $val;
			}
			
		} else {
			
			foreach ($array1 as $key => $value) {
		
				if (! is_array($array1[$key]) || count($array1[$key]) != 1) {
			
					continue;
				}
			
				$array2[ strtolower($key) ] = $value[0];
			}
		}
		
		return $array2;
	}
	

	/**
	 * Unflatten an array so it is usable with Zend_Ldap
	 * 
	 * @param array $array
	 * @return array
	 */
	protected function _unflattenArray(array $array)
	{
		foreach ($array as $key => $value) {
			
			if (is_array($array[$key])) {
				
				continue;
			}
			
			$array[$key] = array($value[0]);
		}
		
		return $array;
	}
	
	
	/**
	 * Returns an available identifier for the given subtree
	 * 
	 * @param string $dn
	 * @param boolean $hash
	 * @return integer|string
	 */
	protected function _getIdentifier($dn = null, $hash = false)
	{
		if ($dn && substr($dn, -1) != ',') $dn .= ',';
		
		$next = $this->_ressource->countChildren($dn . $this->_currentDn) + 1;
		
		return $hash ? base_convert($next, 10, 32) : $next;
	}
	
	
	protected function _connect($string = null)
	{
		/* build username dc */
		$username  = 'cn='; 
		$username .= $this->_uri->getUsername() ? $this->_uri->getUsername() : 'anonymous';
		$username .= ',' . $this->_baseDN;
		
		$this->_currentDn = $this->_baseDN;
		if ($string) $this->_currentDn = $string . ',' . $this->_baseDN;
		
		try {
		/*	$this->_ressource = new Zend_Ldap(array(
									    			'host'     => $this->_uri->getHost()
												   ,'username' => $username
												   ,'password' => $this->_uri->getPassword() ? $this->_uri->getPassword() : '' 
												   ,'baseDn'   => $this->_currentDn
												   ,'allowEmptyPassword'	=> true
												   , 'optReferrals' => true
												   )
											 );
			$this->_ressource->bind(); //$username, $this->_uri->getPassword() ? $this->_uri->getPassword() : '');
		*/

			$this->_ressource = ldap_connect('ldap://' . $this->_uri->getHost());
			
			if ($this->_ressource === false) {
				
				throw new Exception("echec connexion LDAP");
			}
			
			ldap_set_option($this->_ressource, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_bind($this->_ressource, $username, $this->_uri->getPassword());
			
		} catch (Exception $e) {
			
			throw new Exception($e->getMessage());
		}
	}
	
	protected function _disconnect()
	{
		$this->_ressource->disconnect();
	}
}
