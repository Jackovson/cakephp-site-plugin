<?php

/**
 * Add website basic features and tools
 *
 * - users
 * - users authorizations
 * - stats ?
 * - anti spam/ddos
 * - logs (http/errors etc)
 * - status/monitoring
 * - error pages
 *
 */
class SiteBaseComponent extends Object {

	public $components = array('Auth');
	
	// internal data
	protected $_loggedUser = NULL;
	
	public function  redirect($url, $status = null, $exit = true) {
		$this->autoRender = false;
		App::import('Helper','Html');
		$html = new HtmlHelper() ;
		echo 'Redirection : '.$html->url($url);
		exit;

		if($this->RequestHandler->isAjax()){
			// gestion du redirect si on est en ajax : envoi d'une commande javascript
			App::import('Helper','Html');
			$html = new HtmlHelper() ;
			Configure::write('debug', 0);
			$this->autoRender = false;
			echo '<script type="text/javascript">window.location = "'.$html->url($url).'";</script>';
		} else {
			parent::redirect($url, $status, $exit);
		}
	}

	/**
	 * Default auth behaviors :
	 * - check for isAuthorized
	 * -
	 * -
	 * @return <type>
	 */
	public function  isAuthorized(Controller $controller) {
		// How grant access to an action ?
		// - you are admin
		// if not :
		// - have method isAuthorizedMyaction() returning true
		// if no such method :
		// - have method isAuthorizedController() return true
		// if no such method :
		// - check roles from $accessAction (must have each roles)
		// if no action defined in accessAction
		//- check roles from $accessController (must have each roles)
		// if not... not allowed

		$UserModel = $this->_getModel($this->Auth->userModel);
		$user = $this->getUser();
		
		// is admin ?
		if($UserModel->isSuperAdmin($user)) {
			return TRUE;
		}

		// check isAuthorizedMyaction()
		$method = 'isAuthorized'.ucfirst($controller->action);
		if(is_callable(array($controller, $method))) {
			return $controller->{$method}($user);
		}
		
		// check isAuthorizedController()
		$method = 'isAuthorizedController';
		if(is_callable(array($controller, $method))) {
			return $controller->{$method}($user);
		}
		
		// check if roles are defined in $controller->actionRoles
		if(isset($controller->actionsCredentials) AND isset($controller->actionsCredentials[$controller->action])) {
			return $UserModel->checkCredentials($user, $controller->actionsCredentials[$controller->action]);
		}
		
		// check if roles are defined in $controller->controllerRoles
		if(isset($controller->controllerCredentials)) {
			return $UserModel->checkCredentials($user, $controller->controllerCredentials);
		}
		
		return TRUE;
	}

/**
 * Initializes AuthComponent for use in the controller
 *
 * @param object $controller A reference to the instantiating controller object
 * @return void
 * @access public
 */
	function initialize(&$controller, $settings = array()) {
		$this->_set($settings);
	}

/**
 * Main execution method.  Handles redirecting of invalid users, and processing
 * of login form data.
 *
 * @param object $controller A reference to the instantiating controller object
 * @return boolean
 * @access public
 */
	function startup(&$controller) {
		var_dump('startup site');
		return TRUE;
	}

	/**
	 *
	 * @TODO gérer configuration mise en cache, gérer configuration de requete (surtout liste des champs)
	 * @TODO gérer la mise en cache des roles (voir stockage directement)
	 * @TODO rendre tout cela indépendant en passant par le model (+ classe abstraite)
	 * @return <type>
	 */
	public function getUser() {
		if($this->_loggedUser === NULL) {
			$authUser = $this->Auth->user();

			$UserModel = $this->_getModel($this->Auth->userModel);
			$this->_loggedUser = $UserModel->find('first', array('conditions' => array($UserModel->primaryKey => $authUser[$UserModel->alias][$UserModel->primaryKey])));

			// compute credentials
			$this->_loggedUser = $UserModel->cacheCredentials($this->_loggedUser);
		}
		return $this->_loggedUser;
	}

/**
 * Component shutdown.  If user is logged in, wipe out redirect.
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function shutdown(&$controller) {
		debug('shutdown site');
	}

	/**
	 *
	 * @param string $name
	 * @return Model
	 */
	protected function _getModel($name) {
		$model = null;
		if (!$name) {
			$name = $this->userModel;
		}

		$model = ClassRegistry::init($name);

		if (empty($model)) {
			throw new Exception('Model "'.$name.'" not found');
		}

		return $model;
	}
}
