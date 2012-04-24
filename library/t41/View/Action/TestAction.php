<?php

namespace t41\View\Action;

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
 * @version    $Revision: 832 $
 */

use t41\View\Action\AbstractAction,
	t41\ObjectModel,
	t41\ObjectModel\Property,
	t41\Core;

/**
 * Class handling remote actions on objects
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class TestAction extends AbstractAction {

	
	protected $_id = 'object';
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute(array $data = array())
	{
	}

	
	public function reduce(array $params = array())
	{
		/* keep object in registry */
		$this->setContextData('uuid', Core\Registry::set($this->_obj));
	
		$fullAction = $this->_id;
		if ($this->_action) $fullAction .= '/' . $this->_action;
	
		$array = array(
				'event'		=> 'click', //$this->getParameter('event'),
				'action'	=> $fullAction,
				'data'		=> $this->getContext(),
		);
	
		if ($this->_callback) $array['callback'] = $this->_callback;
	
		// add or replace data with optional $params['data'] content
		if (isset($params['extra'])) {
				
			foreach ((array) $params['extra'] as $key => $val) {
	
				$array[$key] = $val;
			}
		}
	
		// return reduced action without parameters
		return $array;
	}
}
