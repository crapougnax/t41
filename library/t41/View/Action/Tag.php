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
 * Class providing a tag-handling AJAX controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class t41_View_Action_Tag extends t41_View_Action_Abstract {

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
		
		$obj = isset($_POST['obj']) ? $_POST['obj'] : (isset($_GET['obj']) ? $_GET['obj'] : null);
		if (is_null($obj)) $this->_ajax->setSendMessage("Aucune référence d'objet valide", t41_Ajax::ERR);
		$this->_obj = t41_Core::cacheGet($obj);
		
		$this->_ajax = new t41_Ajax();
		$this->_ajax->setStatus(t41_Ajax::NOK);
		
		if (! $this->_obj instanceof t41_Form_Tagger ) {
			
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
	
	
	private function _relation()
	{
		if (@$_POST['id'] && $_POST['state']) {
			
			$id = $_POST['id'];
			$state = $_POST['state'];
				
			$this->_ajax->addData('id', $id);
			$this->_ajax->addData('state', $state);
			$this->_ajax->addData('obj', $_POST['obj']);
				
			switch ($state) {
					
				case 'on':
					// Supprimer le tag
					$this->_ajax->setStatus($this->_obj->deleteRelation($id) ? t41_Ajax::OK : t41_Ajax::NOK);
					break;
					
				case 'off':
					// Affecter le tag
					$this->_ajax->setStatus($this->_obj->createRelation($id) ? t41_Ajax::OK : t41_Ajax::NOK);
					break;
			}

			$this->_ajax->send();
		} 
	}
	
	private function _addvalue()
	{
		if (@$_POST['value']) {
				
			$value = trim($_POST['value']);
			
			if (! $this->_obj->getParameter('read_only')) {
				$result = $this->_obj->addValue($value);
			} else {
				$result = false;
			}
			
			$this->_ajax->addData('value', $value);
			
			if ($result!=FALSE) {
				$this->_ajax->addData('insertID', $result);
				$this->_ajax->setStatus(t41_Ajax::OK);
			} else {
				$this->_ajax->setStatus(t41_Ajax::NOK);
			}

			$this->_ajax->send();
		}
	}
	
	private function _suggest()
	{
		if (@$_GET['q']) {
			
			$query = trim(urldecode($_GET['q']));
			$this->_ajax->addData('resultSet', $this->_obj->suggestRelation($query));
			
			$this->_ajax->setStatus(t41_Ajax::OK);
			$this->_ajax->send();
		}
	}
	
}