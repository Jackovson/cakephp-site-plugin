<?php
/**
 * CredentialableBehavior class
 *
 * @uses          ModelBehavior
 * @package       site
 * @subpackage    site.models.behaviors
 */
abstract class CredentialableBehavior extends ModelBehavior {

	public $settings = array();
	public $_defaultSettings = array(
	);
	
	
	/**
	 * setup method
	 *
	 * @param mixed $Model
	 * @param array $config array()
	 * @return void
	 * @access public
	 */
	public function setup(&$Model, $config = array()) {
		$this->settings[$Model->alias] = array_merge($this->_defaultSettings, $config);
	}

	// check credentials
	abstract public function checkCredentials(Model $Model, array $conditionCredentials, array $credentials);

	// add/compute credentials if needed to the user element
	public function cacheCredentials(Model $Model, array $user) {
		if(!isset($user[$Model->alias]['_credentials'])) {
			$user = $this->refreshCredentials($Model, $user);
		}
		return $user;
	}

	// get credentials from user element (compute them if needed)
	public function getCredentials(Model $Model, array $user) {
		if(!isset($user[$Model->alias]['_credentials'])) {
			$user = $this->cacheCredentials($Model, $user);
		}
		return $user[$Model->alias]['_credentials'];
	}

	// re compute user credentials
	public function refreshCredentials(Model $Model, array $user) {
		$user[$Model->alias]['_credentials'] = $this->computeCredentials($Model, $user);
		return $user;
	}

	// compute credentials
	abstract public function computeCredentials(Model $Model, array $user);
	
	/**
	 * Is user super admin ?
	 * 
	 * @param Model $Model
	 * @param array $user
	 * @return boolean
	 */
	public function isSuperAdmin(Model $Model, array $user) {
		return $this->haveCredential($Model, $user, 'superadmin');
	}
	
	// check if a user have a credential
	abstract public function haveCredential(Model $Model, array $user, $credential);
}