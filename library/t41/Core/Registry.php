<?php

namespace t41\Core;

use t41\ObjectModel,
	t41\Core\UUID,
	t41\Core,
	t41\Exception;


class Registry {

	/**
	 * Cached-data store
	 * @var array
	 */
	static protected $_store = null;
	
	
	/**
	 * Datastore identifier
	 * @var string
	 */
	static public $storeId = 'registry_store';
	
	
	static public function set($obj, $id = null, $force = false)
	{
		//self::loadStore();
		if (! $obj instanceof ObjectModel\ObjectModelAbstract  && ! $obj instanceof ObjectModel\ObjectUri) {
			
			throw new Exception("no object or of unrecognized heritance");
		}
		
		if (is_null($id)) {
			
			if (($obj instanceof ObjectModel\BaseObject || $obj instanceof ObjectModel\DataObject) && $obj->getUri()) {
				
				$prefix = ($obj instanceof ObjectModel\BaseObject) ? 'obj_' : 'do_';
				
				$id = $prefix . md5($obj->getUri()->asString());
				
			} else {

				$id = UUID::v4();
			}
		}
		
		Core::cacheSet($obj, $id, $force);
		return $id;
	}
	
	
	static public function get($uuid)
	{
		return Core::cacheGet($uuid);
		
		//self::loadStore();
		if (isset(self::$_store[$uuid])) {
			
			return self::unserialize(self::$_store[$uuid]);
		}
	}
	
	
	static public function loadStore()
	{
		if (is_null(self::$_store)) {
			self::$_store = Core::cacheGet(self::$storeId);
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
