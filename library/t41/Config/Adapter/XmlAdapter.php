<?php

namespace t41\Config\Adapter;

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
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */


use t41\Config;

/**
 * Class providing basic functions needed to manage XML Configuration files
 *
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class XmlAdapter extends AdapterAbstract {

	
	public function validate()
	{
		return true;
		
		$luie = libxml_use_internal_errors();
		
		libxml_use_internal_errors(true);
		
		/* XSD validation file -if exists- is based on file name */
		$xsdFileName = substr( $this->_filePath, strrpos($this->_filePath, DIRECTORY_SEPARATOR) + 1 );	
		$xsdFileName = substr( $xsdFileName, 0, strpos($xsdFileName, '.') );
		
		$xsdFileName = Config\Loader::findFile('xsd/' . $xsdFileName . '.xsd');
		
		if (is_null($xsdFileName)) {

			return true;
		
		} else {
		
			$doc = new \DOMDocument();
			$doc->load($this->filePath);
			$validate = $doc->schemaValidate( $xsdFileName );
			libxml_use_internal_errors($luie);

			return $validate;
		}
	}
	
	
	/**
	 * Method to load the Configuration file
	 * 
	 * @return array
	 */
	public function load()
	{		
		if (is_null($this->_filePath) || ! $this->validate()) {
			
			throw new Exception ('The config file ' . $this->_filePath . ' is not valid.');
		}
		
		$xml = simplexml_load_file($this->_filePath);
		
		if (! $xml instanceof \SimpleXMLElement) {
			
			throw new Exception("Error parsing $this->_filePath");
		}
		
		$array = $this->_loadElement($xml);
		
		return $array;
	}	
	
	
	/**
	 * Protected recursive method to load a simpleXMLElement into an array 
	 * while preserving identifiers keys
	 *
	 * @param SimpleXMLElement $xml 
	 * @return array
	 */
	protected function _loadElement(\SimpleXMLElement $xml)
	{
		$array = array();
		
		foreach ($xml->attributes() as $key => $value) {
			
			if (in_array($key, $this->_identifiers)) {
				
				$array[$key] = $this->_castValue((string) $value);
			}
		}
		
		foreach ($xml->children() as $key => $value) {		
			
			/* zero is a valid id */
			if (! empty($value->attributes()->id) || $value->attributes()->id == '0') {
				
				$key = (string) $value->attributes()->id;
				unset($value->attributes()->id);
			
			} else if (! empty($value->attributes()->alias)) {
				
				$key = (string) $value->attributes()->alias;
				unset($value->attributes()->alias);
				
			} else if (! empty($value->attributes()->lang)) {

				$key = (string) $value->attributes()->lang;
				unset($value->attributes()->lang);
			}

			if (count($value->children()) > 0) {
				
				$array[$key] = $this->_loadElement($value);
			
			} else {
				
				$array[$key] = $this->_castValue((string) $value);
			}	
		}
		
		return $array;	
	}

	
	/**
	 * Save a Configuration array in a file
	 * 
	 * @var array $config Configuration array
	 * @param bool $add Add data on true, overwrite on false
	 * @todo to be implemented
	 */
	public function save(array $config, $add = true)
	{
		throw new Exception("NOT YET IMPLEMENTED");
	}
}
