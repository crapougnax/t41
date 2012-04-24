<?php

namespace t41\Core;

class Status {

	
	const CREATED	= 1;
	
	const GRANTED	= 2;
	
	const DENIED	= 4;
	
	
	/**
	 * @var integer
	 */
	protected $_code;
	
	protected $_message;
	
	protected $_context = array();
	
	
	public function __construct($message, $code = null, $context = null) {
		
		$this->_message = $message;
		
		$this->_code = $code;
		
		if ($context) {
			// @todo handle various context types
			$this->_context = $context;
		}
	}
	
	
	public function getCode()
	{
		return $this->_code;
	}
	
	
	public function getMessage()
	{
		return $this->_message;
	}
	
	
	public function getContext()
	{
		return $this->_context;
	}
}
