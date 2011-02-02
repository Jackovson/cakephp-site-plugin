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
 *
 */
class SiteComponent extends Object {

	public $components = array('Auth');

	/**
	 * list of roles for each action
	 * @var <type>
	 */
	public $accessActions = array(
		/*
		'actionname' => array(),
		//*/
	);

	/**
	 * list of roles for this controller
	 * @var <type>
	 */
	public $accessController = array(/*
		'admin',
		'moderator',//*/
	);

	
	// internal data
	protected $_loggedUser = NULL;


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
		// if no method :
		// - check roles from $accessAction (must have each roles)
		// if no action defined in accessAction
		//- check roles from $accessController (must have each roles)
		// if not... not allowed

		$user = $this->getUser();

		debug($user);
		//exit;


		return FALSE;
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

			// get computed roles
			$this->_loggedUser['User']['_roles'] = $this->computeRoles($this->_loggedUser);
		}
		return $this->_loggedUser;
	}

	public function getRoles(array $user) {
		if(!empty())
	}

	public function refreshRoles()

	public function computeRoles(array $user) {

		$roles = array();

		// compute roles from groups
		if(!empty($user['UserGroups'])) {
			// add all roles
			foreach($user['UserGroups'] as $userGroup) {
				if(!empty($userGroup['UserGroup']['haveRoles'])) {
					foreach($userGroup['UserGroup']['haveRoles'] as $role) {
						$roles[$role] = $role;
					}
				}
			}

			// remove all no roles
			foreach($user['UserGroups'] as $userGroup) {
				if(!empty($userGroup['UserGroup']['dontHaveRoles'])) {
					foreach($userGroup['UserGroup']['dontHaveRoles'] as $role) {
						unset($roles[$role]);
					}
				}
			}
		}// end groups

		// and personnal roles
		if(!empty($user['User']['haveRoles'])) {
			foreach($user['User']['haveRoles'] as $role) {
				$roles[$role] = $role;
			}
		}
		if(!empty($user['User']['dontHaveRoles'])) {
			foreach($user['User']['dontHaveRoles'] as $role) {
				unset($user[$role]);
			}
		}

		return $roles;
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
