<?php


use t41\Core;

/**
 * Assets delivery controller
 * 
 * @author
 * @version 
 */

use t41\ObjectModel\MediaObject;

require_once 'Zend/Controller/Action.php';

/**
 * Assets elements loader
 *
 */
class t41_MediasController extends Zend_Controller_Action {


	public function uploadAction()
	{
		// voir plus bas la classe uploader
		
		error_reporting(0);
		ini_set('max_execution_time', 360);
		
		$allowedExtensions = array('jpg','jpeg','png', 'pdf');
		$sizeLimit = 10 * 1024 * 1024;
		$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
		
		$folder = '/tmp/';
		
		if (is_dir($folder) && is_writable($folder)) {
			$result = $uploader->handleUpload($folder);
			exit(htmlspecialchars(json_encode($result), ENT_NOQUOTES));
		} else {
			// @TODO gestion des erreurs hors-upload
			exit();
		}
	}
	
	
	public function downloadAction()
	{
		if ($this->_getParam('obj')) {
			$response = $this->getResponse();
			$etag = md5($this->_getParam('obj'));
			
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
				$this->_sendResponse(304);
			}

			$media = new MediaObject(base64_decode($this->_getParam('obj')));

			if (! $media->mime) {
				$this->_sendResponse(404);
			}
			
			$response->setHeader('Content-Type', $media->mime);
			$response->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', $media->label));
			$response->setHeader('ETag', $etag);
			if (Core::$env == Core::ENV_PROD) {
				$response->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time()+30*86400));
			}
			$response->setBody($media->loadBlob('media'));
			$this->_sendResponse();

		} else {
			$this->_sendResponse(404);
		}
	}
	
	
	protected function _sendResponse($code = null)
	{
		if ($code) {
			$this->getResponse()->setHttpResponseCode($code);
		}
		$this->getResponse()->sendResponse();
		exit();		
	}
}





/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {

	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	*/
	function save($path) {

		if (isset($_FILES['qqfile'])) {

			$file = $_FILES['qqfile'];

			if (isset($file['tmp_name'])) {
				return move_uploaded_file($file['tmp_name'], $path);
			}
		} else {

			$input = fopen("php://input", "r");
			$temp = tmpfile();
			$realSize = stream_copy_to_stream($input, $temp);
			fclose($input);

			if ($realSize != $this->getSize()){
				return false;
			}

			$target = fopen($path, "w");
			fseek($temp, 0, SEEK_SET);
			stream_copy_to_stream($temp, $target);
			fclose($target);

			return true;
		}
	}


	function getName() {
		$name = $_GET['qqfile'];
		$ext = substr($name, strrpos($name, '.'), strlen($name)-strrpos($name, '.'));
		return $name;
	}


	function getSize() {
		if (isset($_SERVER["CONTENT_LENGTH"])){
			return (int)$_SERVER["CONTENT_LENGTH"];
		} else {
			throw new Exception('Getting content length is not supported.');
		}
	}
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {
		if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
			return false;
		}
		return true;
	}
	function getName() {
		return $_FILES['qqfile']['name'];
	}
	function getSize() {
		return $_FILES['qqfile']['size'];
	}
}

class qqFileUploader {
	
	private $allowedExtensions = array();
	private $sizeLimit = 10485760;
	private $file;
	private $uploadName;

	function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
		$allowedExtensions = array_map("strtolower", $allowedExtensions);

		$this->allowedExtensions = $allowedExtensions;
		$this->sizeLimit = $sizeLimit;

		$this->checkServerSettings();

		if (isset($_GET['qqfile'])) {
			$this->file = new qqUploadedFileXhr();
		} elseif (isset($_FILES['qqfile'])) {
			$this->file = new qqUploadedFileForm();
		} else {
			$this->file = false;
		}
	}

	public function getUploadName(){
		if( isset( $this->uploadName ) )
			return $this->uploadName;
	}

	public function getName(){
		if ($this->file)
			return $this->file->getName();
	}

	private function checkServerSettings(){
		$postSize = $this->toBytes(ini_get('post_max_size'));
		$uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

		if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
			$size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
			return array('error' => 'Veuillez augmenter les valeurs de post_max_size et upload_max_filesize à ' . $size);
			die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
		}
	}

	private function toBytes($str){
		$val = trim($str);
		$last = strtolower($str[strlen($str)-1]);
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}

	/**
	 * Returns array('success'=>true) or array('error'=>'error message')
	 */
	function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
		if (!is_writable($uploadDirectory)){
			return array('error' => "Erreur Serveur: impossible d'écrire dans le dossier de destination.");
		}

		if (!$this->file){
			return array('error' => 'Aucun fichier envoyé.');
		}

		$size = $this->file->getSize();

		if ($size == 0) {
			return array('error' => 'Le fichier est vide.');
		}

		if ($size > $this->sizeLimit) {
			return array('error' => 'Le fichier est trop lourd.');
		}

		$pathinfo = pathinfo($this->file->getName());
		$filename = $pathinfo['filename'];
		$hash = md5(uniqid());
		$ext = @$pathinfo['extension'];		// hide notices if extension is empty

/* 		if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
			$these = implode(', ', $this->allowedExtensions);
			return array('error' => 'Ce type de fichier n\'est pas autorisé ('.$these.').');
		} */

		$ext = ($ext == '') ? $ext : '.' . $ext;

		if(!$replaceOldFile){
			/// don't overwrite previous files that were uploaded
			while (file_exists($uploadDirectory . $filename . $ext)) {
				$filename .= rand(10, 99);
			}
		}

		$this->uploadName = $filename . $ext;

		if ($this->file->save($uploadDirectory . $hash . $ext)){
			return array('success' => true, 'filename' => $filename . $ext, 'hash' => $hash . $ext);
		} else {
			return array('error'=> 'Impossible de sauver le fichier envoyé.' .
			'L\'envoi a été annulé, ou le serveur a rencontré un problème.');
		}
	}
}
