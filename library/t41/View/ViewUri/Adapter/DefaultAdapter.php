<?php

namespace t41\View\ViewUri\Adapter;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://t41.quatrain.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@quatrain.com so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 * @version    $Revision: 650 $
 */

use t41\View\ViewUri\Adapter;

/**
 * Adapter for the default Uri scheme type (Zend Framework based)
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 */

class DefaultAdapter extends AbstractAdapter {

	/**
	 * Character used to glue key/pairs after collation
	 *
	 * @var string
	 */
	protected $_pairsSeparator	= '/';
	
	/**
	 * Character used to assign value to key in pairs
	 * typically '=' but not always
	 *
	 * @var string
	 */
	protected $_assignSeparator = '/';
	
	/**
	 * Character used to glue base uri and arguments string
	 *
	 * @var string
	 */
	protected $_partsSeparator	= '/';
		
	/**
	 * Array of sundries identifiers used to pass parameters 
	 *
	 * @var array
	 */
	protected $_identifiers = array('offset' => 't41o', 'search' => 't41w', 'sort' => 't41s');

	
	public function __construct($uriBase = null, $params = null)
	{
		parent::__construct($uriBase, $params);
		
		/*
		 * the two only ways available to get request environment are:
		 * 1. analyse & parse REQUEST_URI
		 * 2. get Zend_Controller_Request_Get object and/or its params
		 * 
		 * @todo code this 
		 */
//		$this->_env = $this->_params['zf_request_object']->getParams();
		
	}
}
