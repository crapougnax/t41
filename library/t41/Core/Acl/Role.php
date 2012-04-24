<?php

namespace t41\Core\Acl;

use t41\Core\Acl,
	t41\ObjectModel\ObjectModelAbstract;


class Role extends ObjectModelAbstract {

	
	protected $_label;
	
	protected $_privileges;
	
	
	public function __construct($id, array $params = array())
	{
		parent::__construct($id, $params);
		$this->setPrivileges(Acl::getGrantedResources($this->getId()));
	}
	
	
	public function setLabel($str)
	{
		$this->_label = $str;
		return $this;
	}
	
	
	public function getLabel()
	{
		return $this->_label;
	}
	
	
	public function setPrivileges(array $privileges)
	{
		$this->_privileges = $privileges;
	}
	
	
	public function getPrivileges()
	{
		return $this->_privileges;
	}
	
	
	public function granted($resource)
	{
		return (in_array($resource, $this->_privileges));
	}
}
