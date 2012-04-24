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
 * Class providing an AJAX form controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class t41_View_Action_Form extends t41_View_Action_Abstract {

	/**
	 * Object class
	 *
	 * @var string
	 */
	protected $_objClass = 't41_Object_Abstract';

	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute()
	{
		
		if (isset($_POST['func'])) {
			$func = '_' . $_POST['func'];
			if (method_exists($this, $func)) {
				return $this->$func();
			}
		}
	}
	
	private function _initvalidator()
	{
		$ajax = new t41_Ajax();
		$ajax->setStatus(t41_Ajax::NOK);
		
		if (isset($_POST['obj'])) {
			
			/* @var $obj t41_Form_Mask */
			$obj = t41_Core::cacheGet($_POST['obj']);
			$deco = t41_View_Decorator::factory($obj);
			
			foreach ($deco->getJsArgs() as $key => $val) {
				$ajax->addData($key, $val);
			}
			$ajax->setStatus(t41_Ajax::OK);
			
		} else {

			$ajax->setMessage("Unavailable Cached Object");
		}
		
		header('Content-Type: application/json; charset=UTF-8');
		echo $ajax;
		exit;
	}
	
	private function _submit()
	{

		$ajax = new t41_Ajax();
		$ajax->setStatus(t41_Ajax::NOK);
		
		if (! isset($_POST['values'])) {
			
			$ajax->setSendMessage("Données manquantes");
		}
		
		if (! isset($_POST['obj'])) {
			
			$ajax->setSendMessage("Référence d'objet manquante");
		}
		
		/* @var $obj t41_Form_Mask */
		$obj = t41_Core::cacheGet($_POST['obj']);

		$pairs = explode('&', $_POST['values']);
		$data = array();
			
		foreach ($pairs as $pair) {

			$elem = explode('=', $pair);
			$data[$elem[0]] = urldecode($elem[1]);
		}

		try {
			$res = $obj->save($data);
			
				
			if ($res === false) {
				
				if ($obj->getParameter('redirect_on_failure')) {
					$ajax->addData('redirect', $obj->getParameter('redirect_on_failure'));
				}
				$ajax->setSendMessage($res);
			} else {
							
				if ($obj->getParameter('redirect_on_success')) {
					$ajax->addData('redirect', $obj->getParameter('redirect_on_success'));
				}
				$ajax->setSendMessage($res, t41_Ajax::OK);
			}
		} catch (Exception $e) { 
				
			$ajax->setSendMessage($e->getMessage(), t41_Ajax::ERR);
		}

		exit;
	}
	
	private function _delete()
	{
		$ajax = new t41_Ajax();
		$ajax->setStatus(t41_Ajax::NOK);
		
		if (! isset($_POST['id']) || ! isset($_POST['t41_object'])) {
			$ajax->setSendMessage("Références d'objet manquante");
		}
		
		$id = $_POST['id'];
		$class = $_POST['t41_object'];
		$obj = new $class($id);
		
			
		
		
		if ($obj->delete()) {
			$ajax->setStatus(t41_Ajax::OK);	
		} else {
			$ajax->setStatus(t41_Ajax::NOK);
		}
		
		//$obj = t41_Core::cacheGet($_POST['obj']);
		
		//var_dump($obj->delete());
		exit($ajax);
		
	}
	
}