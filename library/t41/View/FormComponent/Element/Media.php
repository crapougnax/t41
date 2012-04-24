<?php

namespace t41\View\Form\Element;

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
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\Parameter;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class MediaElement extends ElementAbstract {


	protected $_path;
	
	
	protected $_allowedMimes;
	
	
	public function __construct($id = null, array $params = null)
	{
		$this->_setParameterObjects(array('max_file_size'	=> new Parameter(Parameter::INTEGER, 2000000)
										 )
								   );
		
		parent::__construct($id, $params);
		
		$this->_path = ($this->_asArray['t41_field_val_field_label']) ? $this->_asArray['t41_field_val_field_label'] : 'media';
		$this->_allowedMimes = ($this->_asArray['t41_field_val_validator']) ? $this->_asArray['t41_field_val_validator'] : '*';
	}
	
	
	public function formatValue($value = null)
	{
		if ($value) {
			
			$data = \Zend_Json::decode($value);
			
			$path = $this->_path;

			// @todo find a better way to handle various path and url
			if (defined('MEDIA_PREFIX')) $path = MEDIA_PREFIX . $path;
			
			return sprintf('<img src="%s/%s" title="%s"/>', $path, $data['path'], $data['name']);
			
		} else {
			
			return null;
		}
	}
	
	
	public function saveFile($fileKey = null)
	{
		if (is_null($fileKey)) return false;
		
		$hashLevels = 2;
		
		// get media from cache
		$fileData = t41\Core::cacheGet($fileKey);
		
		if (! is_array($fileData)) {
			
			return "Unable to get object $fileKey from cache";
		}
		
		$fileName = md5(microtime());
		if (!empty($fileData['ext'])) $fileName .= '.' . $fileData['ext'];

		$fileBasePath = t41\Core::getBasePath() . $this->_path . DIRECTORY_SEPARATOR;
		$filePath = '';
		
		for ($i = 0 ; $i < $hashLevels ; $i++) {
			
			$filePath .= substr($fileName, $i, 1) . DIRECTORY_SEPARATOR;
			
			if (! file_exists($fileBasePath . $filePath)) {
				
				if (! mkdir($fileBasePath . $filePath, 0755)) {
					
					return "Failed to create $fileBasePath$filePath directory";
				}
			}
		}
		
		if (file_put_contents($fileBasePath . $filePath . $fileName, $fileData['data'])) {
			$fileData['path'] = $filePath . $fileName;
			unset($fileData['data']);
		}

		// empty cache
//		t41_Core::cacheSet($fileKey);
		
		return $fileData;
	}
	
	
	/**
	 * Cache uploaded file and sent the cache key
	 * The file will be associated with the field when the form where the field appears will be submitted
	 *
	 * @param array $fileData
	 * @return string
	 */
	public function cacheFile(array $fileData)
	{
		$fileData['data'] = file_get_contents($fileData['tmp_name']);
		$fileExt = substr($fileData['name'], strpos($fileData['name'], '.') + 1);
		
		$cacheKey = md5(microtime());
		t41\Core::cacheSet($cacheKey, array('name' => $fileData['name'], 'data' => $fileData['data'], 'ext' => $fileExt));
		
		return array('cacheKey' => $cacheKey, 'ext' => $fileExt);
	}
}
