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
			'pdf'	=> 'application/pdf',
			
			'xlsx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xltx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'potx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'ppsx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'pptx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'sldx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'dotx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'xlam'	=> 'application/vnd.ms-excel.addin.macroEnabled.12',
			'xlsb'	=> 'application/vnd.ms-excel.sheet.binary.macroEnabled.12'
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
	
	
	/**
	 * (non-PHPdoc)
	 * @see \t41\ObjectModel\BaseObject::__toString()
	 */
	public function __toString()
	{
		return $this->getUri() ? $this->getUri()->__toString() : null;
	}
}
