<?php

namespace t41\ObjectModel;

use t41\ObjectModel\BaseObject;

class MediaObject extends BaseObject {

	/**
	 * Array of acceptable mime types
	 * @var array
	 */
	protected $_mimetypes = array(
			'js'	=> 'application/javascript',
			'css'	=> 'text/css',
			'png'	=> 'image/png',
			'gif'	=> 'image/gif',
			'jpg'	=> 'image/jpeg',
			'ttf'   => 'application/octet-stream',
			'doc'	=> 'application/msword',
			'pdf'	=> 'application/pdf'
	);
	
	
	
	public function defineMimeAndExtensionFromData()
	{
		if ($this->getData()) {
			
			// @todo code this
		}
		
		return true;
	}
	
	
	public function setMime($mime)
	{
		$ext = array_search($mime, $this->_mimetypes);
		if ($ext) {
			$this->setProperty('mime', $mime);
			$this->setProperty('extension', $ext);
		} else {
			throw new Exception("No mimetype for: " . $mime);
		}
		return $this;
	}
}
