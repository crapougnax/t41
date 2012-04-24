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
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\View\ViewUri\Adapter;


/**
 * Adapter for URI manipulation in GET context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class GetAdapter extends AbstractAdapter {

	/**
	 * Character used to glue key/pairs after collation
	 *
	 * @var string
	 */
	protected $_pairsSeparator	= '&';
	
	/**
	 * Character used to assign value to key in pairs
	 *
	 * @var string
	 */
	protected $_assignSeparator = '=';
	
	/**
	 * Character used to glue base uri and arguments string
	 *
	 * @var string
	 */
	protected $_partsSeparator	= '?';
		
	/**
	 * Array of sundries identifiers used to pass parameters 
	 *
	 * @var array
	 */
	protected $_identifiers = array('offset' => 't41o', 'search' => 't41w', 'sort' => 't41s', 'query' => 't41q');

	
	public function __construct($uriBase = null, $params = null)
	{
		$this->setEnv($_GET);
		
		parent::__construct($uriBase, $params);
	}
}
