<?php
namespace t41\ObjectModel\Rule;

class RuleElement {

	
	const TYPE_PROPERTY = 1;
	
	const TYPE_METHOD	= 2;
	
	const TYPE_FUNCTION = 4;
	
	
	protected $_type;
	
	
	protected $_value;
	
	
	protected $_argument;
	
	
	public function isProperty()
	{
		return ($this->_type == self::TYPE_PROPERTY);
	}
	
	
	public function isMethod()
	{
		return ($this->_type == self::TYPE_METHOD);
	}
	

	public function isFunction()
	{
		return ($this->_type == self::TYPE_FUNCTION);
	}
	
	
	public function setType($type)
	{
		$this->_type = $type;
		return $this;
	} 
	
	
	public function setValue($value)
	{
		$this->_value = $value;
		return $this;
	}
	

	public function setArgument($arg)
	{
		$this->_argument = $arg;
		return $this;
	}
	
	
	public function getType()
	{
		return $this->_type;
	}
	
	
	public function getValue()
	{
		return $this->_value;
	} 
	
	
	public function getArgument()
	{
		return $this->_argument;
	}
}
