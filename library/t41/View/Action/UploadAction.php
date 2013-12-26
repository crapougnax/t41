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

use t41\Core;
use t41\ObjectModel\ObjectUri;
use t41\Backend\Condition;
use t41\ObjectModel\Property;
use t41\ObjectModel;

/**
 * Class providing an AJAX autocompletion controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class UploadAction extends AbstractAction {

	
	protected $_id = 'action/upload';
	
	
	protected $_callbacks = array();
	
	
	protected $_context = array('minChars' => 3, 'displayMode' => 'list', 'defaultSelect' => true);
	
	
	/**
	 * Object class
	 *
	 * @var string
	 */
	protected $_objClass = 't41\View\FormComponent\Element\MediaElement';
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute($params = null)
	{
	}

	
	public function reduce(array $params = array())
	{
		$array = parent::reduce($params);
		
		return $array;
	}
}
