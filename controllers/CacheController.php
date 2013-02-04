<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core,
	t41\ObjectModel,
	t41\ObjectModel\Collection,
	t41\Backend,
	t41\View,
	t41\View\Action;

require_once 'Zend/Controller/Action.php';

class t41_CacheController extends Zend_Controller_Action {

	
	protected $_cache;
	
	
	public function init()
	{
		$this->_cache = Core::cacheGetAdapter();
	}
	
	
	public function indexAction()
	{
		//var_dump($cache->getFillingPercentage());
		$html = '<table border="1"><tr><th>Id</th><th>Type</th><th>Date</th><th>TTL</th><th>Expire</th><th>&nbsp;</th></tr>';
		foreach ($this->_cache->getIds() as $id) {
			$metas = $this->_cache->getMetadatas($id);
			$html .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s | %s</td></tr>'
							, $id
							, implode(' ', $metas['tags'])
							, date('d/m/Y H:i:s', $metas['mtime'])
							, $metas['expire'] - $metas['mtime']
							, date('d/m/Y H:i:s', $metas['expire'])
							, '<a href="/t41/cache/view/id/' . rawurlencode($id) . '">View</a>'
							, '<a href="/t41/cache/delete/id/' . rawurlencode($id) . '">Delete</a>'
					);
		}
		$html .= '</table>';
		
		echo $html;
	}
	
	
	public function viewAction()
	{
		if (($cached = Core::cacheGet($this->_getParam('id'))) !== false) {
			echo '<pre>' . print_r($cached,true) . '<pre>';
		} else {
			throw new \Exception("Unable to find a cached object with this id: " . $this->_getParam('id'));
		}
		exit();
	}
	
	
	public function deleteAction()
	{
		$this->_cache->remove($this->_getParam('id'));
		$this->_redirect('/t41/cache');
		exit();
	}
	
	
	public function cleanAction()
	{
		$this->_cache->clean();
	}
}
