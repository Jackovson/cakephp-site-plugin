<?php
App::import('Behavior', 'Site.Credentialable');
App::import('Vendor', 'Site.SimpleCredential');
/**
 * CredentialableBehavior class
 *
 * @uses          ModelBehavior
 * @package       site
 * @subpackage    site.models.behaviors
 */
class SimpleCredentialableBehavior extends CredentialableBehavior {

	/**
	 * name property
	 *
	 * @var string 'Schemaless'
	 * @access public
	 */
	public $name = 'SimpleCredentialable';

	
	/**
	 * setup method
	 *
	 *
	 * @param mixed $Model
	 * @param array $config array()
	 * @return void
	 * @access public
	 */
	public function setup(&$Model, $config = array()) {
		$config['handler'] = new SimpleCredential();
		$this->settings[$Model->alias] = array_merge($this->_defaultSettings, $config);
	}


	public function checkCredentials(Model $Model, array $conditionCredentials, array $credentials) {
		return $this->_getManager($Model)->checkCredentials($conditionCredentials, $credentials);
	}
	
	public function haveCredential(Model $Model, array $user, $credential) {
		return $this->checkCredentials($Model, array($credential), $this->getCredentials($Model, $user));
	}

	public function  computeCredentials(Model $Model, array $user) {
		$credentials = array();

		// compute groups credentials
		if(!empty($user['UserGroups'])) {
			// add all roles
			foreach($user['UserGroups'] as $userGroup) {
				if(!empty($userGroup['UserGroup']['haveCredentials'])) {
					foreach($userGroup['UserGroup']['haveCredentials'] as $credential) {
						$credentials[$credential] = $credential;
					}
				}
			}

			// remove all no roles
			foreach($user['UserGroups'] as $userGroup) {
				if(!empty($userGroup['UserGroup']['dontHaveCredentials'])) {
					foreach($userGroup['UserGroup']['dontHaveCredentials'] as $credential) {
						unset($credentials[$credential]);
					}
				}
			}
		}// end groups

		// and personnal credentials
		if(!empty($user['User']['haveCredentials'])) {
			foreach($user['User']['haveCredentials'] as $credential) {
				$credentials[$credential] = $credential;
			}
		}
		if(!empty($user['User']['dontHaveCredentials'])) {
			foreach($user['User']['dontHaveCredentials'] as $credential) {
				unset($user[$credential]);
			}
		}

		return $credentials;
	}

	/**
	 * Get the credential manager
	 * @param Model $Model
	 * @return SimpleCredential
	 */
	protected function _getManager(Model $Model) {
		return $this->settings[$Model->alias]['handler'];
	}
}