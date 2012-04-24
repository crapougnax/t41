<?php

namespace t41\Core;

use t41\ObjectModel,
	t41\Core\UUID,
	t41\Core;


class Registry {

	
	static protected $_store = array();
	
	
	static public function set($obj)
	{
		self::$_store = Core::cacheGet('registry_store');
		
		if (! $obj instanceof ObjectModel\ObjectModelAbstract  && ! $obj instanceof ObjectModel\ObjectUri) {
			
			throw new \t41\Exception("no object or of unrecognized heritance");
		}
		
		$id = UUID::v4();
		
		self::$_store[$id] = self::serialize($obj);
		Core::cacheSet(self::$_store, 'registry_store');
		
		return $id;
	}
	
	
	static public function get($uuid)
	{
		self::$_store = Core::cacheGet('registry_store');
		
		if (isset(self::$_store[$uuid])) {
			
			return self::unserialize(self::$_store[$uuid]);
		}
	}
	
	
	static public function serialize($obj)
	{
		return array('_class' => get_class($obj), 'content' => gzcompress(serialize($obj)));
	}
	
	static public function unserialize($cached)
	{
		if (isset($cached['_class']) && ! class_exists($cached['_class'])) {

			\Zend_Loader::loadClass($cached['_class']);
		}
		
		return unserialize(gzuncompress($cached['content']));
	}
}
