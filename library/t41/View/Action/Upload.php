<?php
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

/** Required files */
require_once 't41/View/Action/Abstract.php';

/**
 * Class providing an upload handling AJAX controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class t41_View_Action_Upload extends t41_View_Action_Abstract {

	/**
	 * Object class
	 *
	 * @var string
	 */
	protected $_objClass = 't41_Form_Element_Media';
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute()
	{
		
		$obj = isset($_POST['obj']) ? $_POST['obj'] : (isset($_GET['obj']) ? $_GET['obj'] : null);
		if (is_null($obj)) $this->_ajax->setSendMessage("Aucune référence d'objet valide", t41_Ajax::ERR);
		$this->_obj = t41_Core::cacheGet($obj);
		
		$this->_ajax = new t41_Ajax();
		$this->_ajax->setStatus(t41_Ajax::NOK);
		
		if (! $this->_obj instanceof $this->_objClass ) {
			
			$this->_ajax->setSendMessage("L'objet passé en référence n'a pas été retrouvé", t41_Ajax::ERR);
		}
		
		
		$func = isset($_POST['func']) ? $_POST['func'] : (isset($_GET['func']) ? $_GET['func'] : null);
		
		if (!is_null($func)) {
			$func = '_' . $func;
			if (method_exists($this, $func)) {
				return $this->$func();
			}
		}
		
		return false;
	}
	
	
	private function _upload()
	{
		t41_View::setDisplayContext('ajax');
		
		$ajax = new t41_Ajax();
		
		$res = $this->_obj->cacheFile($_FILES['Filedata']);
		
		if ($res !== false) {
			
			$ajax->setStatus(t41_Ajax::OK);
			foreach ($res as $key => $val) {
				$ajax->addData($key, $val);
			}				
		} else {
			
			$ajax->setStatus(t41_Ajax::NOK, $res);
		}
		
		exit($ajax);
	}
}