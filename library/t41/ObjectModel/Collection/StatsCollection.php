<?php

namespace t41\ObjectModel\Collection;

use t41\ObjectModel\Exception;
use t41\ObjectModel\Collection;
use t41\ObjectModel\Property\ObjectProperty;

class StatsCollection extends Collection {
	
	
	protected $_statsProps = array();
	
	
	public function __construct($do = null, array $params = null)
	{
		parent::__construct('t41\ObjectModel\Collection\StatsObject', $params);
	}
	
	
	public function setStatsProps($properties)
	{
		$this->_statsProps = array();
		foreach ($properties as $property) {
			$this->_statsProps[$property->getId()] = $property;
		}
		
		return $this;
	}
	
	
	public function toArray($propertyAsKey = null, $callback = null)
	{
		if (is_null($propertyAsKey)) {
			rewind($this->_statsProps);
			$propertyAsKey = key($this->_statsProps);
		}
		
		if (! array_key_exists($propertyAsKey, $this->_statsProps)) {
			throw new Exception("No such property %0 in stat collection", array($propertyAsKey));
		}
		
		$property = $this->_statsProps[$propertyAsKey];
		$key = $property->getId();
		
		$array = array();
		foreach ($this->getMembers() as $member) {
			$group = $member->getGroup();
			if ($property instanceof ObjectProperty) {
				if (! $group[$key]->getUri()) continue;
				$str = sprintf('%s (%d)', $group[$key]->__toString(), $member->getTotal());
				$array[$group[$key]->getIdentifier()] = $str;
			} else {
				$str = sprintf('%s (%d)'
								, $callback ? call_user_func($callback, $group[$key]) : $group[$key]
								, $member->getTotal()
						      );
				$array[$group[$key]] = $str;
			}
		}
		
		if ($property instanceof ObjectProperty) {
			asort($array, SORT_STRING);
		} else {
			ksort($array, SORT_STRING);
		}
		return $array;
	}
}
