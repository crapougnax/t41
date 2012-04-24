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
 * Class providing an association handling AJAX controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class t41_View_Action_Match extends t41_View_Action_Abstract {

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
		
		if (! $this->_obj instanceof t41_Form_Matcher ) {
			
			$this->_ajax->setSendMessage("L'objet passé en référence n'a pas été retrouvé", t41_Ajax::ERR);
		}
		
		$this->_ajax = new t41_Ajax();
		$this->_ajax->setStatus(t41_Ajax::NOK);
		
		$func = isset($_POST['func']) ? $_POST['func'] : (isset($_GET['func']) ? $_GET['func'] : null);
		
		if (!is_null($func)) {
			$func = '_' . $func;
			if (method_exists($this, $func)) {
				return $this->$func();
			}
		}
		
		return false;
	}
	
	
	private function _assoc()
	{
		if ($_POST['id'] && $_POST['status']) {
			
			$id = $_POST['id'];
			$status = $_POST['status'];
			
			if (isset($_POST['value'])) $this->_ajax->addData('value', $_POST['value']);
				
			$this->_ajax->addData('id', $id);
			$this->_ajax->addData('status', $status);
			$this->_ajax->addData('obj', $_POST['obj']);
				
			if ($status=='true') {
				// Créer la relation
				$this->_ajax->setStatus($this->_obj->createRelation($id) ? t41_Ajax::OK : t41_Ajax::NOK);
			} else {
				// Supprimer la relation
				$this->_ajax->setStatus($this->_obj->deleteRelation($id) ? t41_Ajax::OK : t41_Ajax::NOK);
			}
			
		} else {
			$this->_ajax->setSendMessage("Arguments manquants", t41_Ajax::ERR);
		}
		
		$this->_ajax->send();
	}
	
	private function _suggest()
	{
		if (@$_GET['q']) {
			
			$query = trim(urldecode($_GET['q']));
			$this->_ajax->addData('resultSet', $this->_obj->suggestRelation($query));
			
			$this->_ajax->setStatus(t41_Ajax::OK);
			$this->_ajax->send();
		} else {
			$this->_ajax->setSendMessage('Arguments manquants', t41_Ajax::ERR);
		}
		
		$this->_ajax->send();
	}
	
}