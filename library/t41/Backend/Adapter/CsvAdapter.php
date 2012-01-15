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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 905 $
 */

use t41\Backend;
use t41\ObjectModel;

/**
 * Class providing CSV methods to backend
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * 
 */
class CsvAdapter extends FilesystemAdapter {


	protected $_basePath;	
	
	
	/**
	 * Initialiser un backend à partir d'une Uri
	 *
	 * @param t41_Backend_Uri $uri
	 * @param string $alias
	 */
	public function __construct(Backend\BackendUri $uri, $alias = null)
	{
		parent::__construct($uri, $alias);
	}
	
	
	public function create(ObjectModel\DataObject $dataObj = null)
	{
		return file_put_contents($this->_basePath . $dataObj->getUri()->getUrl(), $dataObj);
	}
	
	
	public function read(ObjectModel\DataObject $do)
	{
		/* @todo read line with given index */
		return array() ; //file_get_contents($this->_basePath . $do->getUri()->getUrl());
	}
	
	
	public function update(ObjectModel\DataObject $do)
	{
		throw new Exception(__CLASS__ . " backend doesn't implement the update() method");
	}
	
	
	public function delete(ObjectModel\DataObject $do)
	{
		return unlink($this->_basePath . $do->getUri()->getUrl());
	}
	
	
	public function find(ObjectModel\Collection $collection)
	{
		$url = str_replace('{basepath}', \t41\Core::getBasePath(), $this->_uri->getUrl());
		$class = $collection->getDataObject()->getClass();
		
		if ($this->_mapper) {
			
			$file = $this->_mapper->getDatastore($class);
			
		} else {
			
			throw new Exception("Csv backends need a mapper where datastores are defined");
		}


		if (substr($file, 0, 1) != DIRECTORY_SEPARATOR) $file = $url . $file;

		try { 
				$file = fopen($file, 'r');
				
		} catch (Exception $e) {
			
			throw new Exception("Error opening file: " . $e->getMessage());
		}

		// prepare defined conditions to be used next
		$this->_prepareConditionBlock($collection);
		
		// populate array with relevant objects type
		$uri = new ObjectModel\ObjectUri();
		$uri->setBackendUri($this->_uri);
		$uri->setClass($class);

		if ($collection->getParameter('memberType') != 'uri') {
			
			$do = new ObjectModel\DataObject($class);
		}

		$separator = $this->_mapper->getExtraArg('separator', $class);
		
		if (! $separator) $separator = ',';
		
		$separator = str_replace(array('{tab}'), array("\t"), $separator);
		
		$array = array();
		
		$key = 0; // csv file current line key
		
		while (($data = fgetcsv($file, 1000, $separator)) !== false) {	
			
			if ($this->_mapper->getExtraArg('firstlineisheader', $class) !== false && $key == 0) {
				
				/* ignore first line if it is declared as header */
				$key = 1;
				continue;
			}

			/* test data array against defined conditions */
			if ($this->_testAgainstConditionBlock($data) === false) {
				
				continue;
			}
			
			$uri->setUrl($url . '/' . $key++);
			switch ($collection->getParameter('memberType')) {

				case 'uri':
					$obj = clone $uri;
					break;
					
				case 'data':
					$obj = clone $do;
					$obj->setUri(clone $uri);
					$obj->populate($data, $this->_mapper);
					break;
					
				case 'model':
					$obj = clone $do;
					$obj->setUri(clone $uri);
					$obj->populate($data, $this->_mapper);
					/* @var $obj t41_Object_Model */
					$obj = new $class(null, null, $obj);
					break;
			}
			
			$array[] = $obj;
		}
		
		return $array;
	}
	
	
	/**
	 * Convert an array of t41_Condition instances into an array of preg_match patterns
	 * AND is the only supported mode bewteen conditions right now
	 * AND & OR mode are supported within the clauses of the same condition
	 * 
	 * @todo implement AND mode between conditions
	 * @param t41_Object_Collection $collection
	 */
	protected function _prepareConditionBlock(t41_Object_Collection $collection)
	{
		$array = array();
		
		/* @var $condition t41_Condition */
		foreach ($collection->getConditions() as $condition) {
			
			/* get property id in backend */
			$key = $this->_mapper->propertyToDatastoreName($collection->getClass(), $condition[0]->getProperty()->getId());

			$array[$key] = array();
			
			$clauses = $condition[0]->getClauses();
			$value = array();

			foreach ($clauses as $clause) {
			
				if (! isset($mode)) $mode = isset($clause['mode']) ? $clause['mode'] : Backend\Condition::MODE_AND;
				$ops = $this->_matchOperator($clause['operator']);

				$prefix = $suffix = null;
				
				foreach ($ops as $op) {

					switch ($op) {
				
						case Backend\Condition::OPERATOR_BEGINSWITH:
							$prefix = '^'; 
							break;
						
						case Backend\Condition::OPERATOR_ENDSWITH:
							$suffix = '$';
							break;
							
						default:
							break;
					}
				}
				
				if (is_array($clause['value'])) {
						
					$value[] = $prefix . '[' . implode(",", $clause['value']) . ']' . $suffix;
					
				} else {
					
					$value[] = $prefix . $clause['value'] . $suffix;
				}
			}
			
			$array[$key][$mode] = $value;
			$mode = null;
		}

		$this->_conditionsBlock = $array;
	}
	
	
	/**
	 * Test an array against various preg_match patterns contained in $this->_conditionBlock
	 * 
	 * @todo implement OR mode handling for conditions
	 * @param array $data
	 * @return boolean
	 */
	protected function _testAgainstConditionBlock(array $data)
	{
		foreach ($this->_conditionsBlock as $key => $modes) {
			
			// We don't test value if value is not setted (but we should)
			if (! isset($data[$key])) continue;
			
			foreach ($modes as $mode => $patterns) {

				/* condition status, needed for OR mode */
				$status = false;
				
				foreach ($patterns as $pattern) {
					
					$result = preg_match("/" . $pattern . "/i", $data[$key]);
					//printf('%d -> %s preg %s -> %s<br/>', $key, $data[$key], $pattern, $result);
					
					/* in AND mode, any no-match returns failure */
					if ($mode == Backend\Condition::MODE_AND && $result == 0) {
						return false;
					}
					
					/* in OR mode, one match is enough */
					if ($mode == Backend\Condition::MODE_OR && $result != 0) {

						$status = true;
					}
				}
				
				if ($status == false && $mode == Backend\Condition::MODE_OR) {
					
					return false;
				}
			}
		}

		return true;
	}
}
