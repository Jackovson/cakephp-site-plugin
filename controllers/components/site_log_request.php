<?php
App::import('Model', 'Site.LogRequest');

/**
 * Add access loging and limit :
 *
 * Logs :
 * - url
 * - urlFull
 * - request
 * - requestFull
 * - session_id
 * - ip
 * - time
 * - memory peak usage
 * - memory usage
 *
 * Limit :
 *	 - global limit
 *	 - per controller limit
 *   - per action limit
 *
 * Limit :
 * - per ip
 * - per user
 * - per what ?
 * - per
 *
 */
class SiteLogRequestComponent extends Object {

	public $components = array('RequestHandler');

	protected $logProbability = 1;// 10%
	protected $log = array(
		'url',
		//'urlFull',
		//'request',
		'time',
		'memory_peak',
		'ip',
		'session',
		// related to request
		'http_method',
		'referer',
		'ajax',// is ajax ?
		'mobile',// is called by mobile device ?
		'feed', // RSS or Atom
		'ssl',
		'flash',
	);//

	protected $logData = array();
	protected $shouldLog = FALSE;
	protected $timeStart = NULL;

	protected $model = 'LogRequest';

	/**
	 * Initializes AuthComponent for use in the controller
	 *
	 * @param object $controller A reference to the instantiating controller object
	 * @return void
	 * @access public
	 */
	public function initialize(&$controller, $settings = array()) {
		$this->timeStart = microtime(TRUE);
		var_dump('initialize log access');
		$this->_set($settings);

		$this->controller = $controller;

		$this->shouldLog = (rand(0, 1) <= $this->logProbability);

		if($this->shouldLog) {
			foreach($this->log as $field) {
				$this->buildFieldOnInit($field, $controller);
			}

			$LogModel = $this->_getModel($this->model, $controller);

			$LogModel->create();
			$this->logData['created'] = NULL;
			$this->logData['status'] = 'init';
			$LogModel->save($this->logData);
			$this->logData['_id'] = $LogModel->id;
		}
	}

	public function beforeRedirect(&$controller, $url, $status=null, $exit=true) {
		if($this->shouldLog) {
			foreach($this->log as $field) {
				$this->buildFieldOnShutdown($field, $controller);
			}

			$LogModel = $this->_getModel($this->model, $controller);
			$this->logData['status'] = 'redirect';
			debug($this->logData);
			$LogModel->save($this->logData);
		}
		return TRUE;
	}


	protected function buildFieldOnInit($field, Controller $controller) {
		$value = NULL;
		switch($field) {
			case 'url':
				$url = $controller->params;
				$value = array('controller' => $url['controller'], 'action' => $url['action'], 'plugin' => $url['plugin']);
				break;

			case 'urlFull':
				$value = $controller->params;
				break;
			
			case 'ip':
				$value = $this->RequestHandler->getClientIp(TRUE);
				break;

			case 'session':
				$this->controller->Session->read();// read session to be sure component is active
				$value = $this->controller->Session->id();
				break;

			case 'memory_peak':
				$value = memory_get_peak_usage(TRUE);
				break;

			case 'memory':
				$value = memory_get_usage(TRUE);
				break;

			case 'http_method':
				$value = env('REQUEST_METHOD');
				break;

			case 'referer':
				$value = $this->RequestHandler->getReferer();
				break;

			case 'ajax':
				$value = $this->RequestHandler->isAjax();
				break;

			case 'mobile':
				$value = $this->RequestHandler->isMobile();
				break;

			case 'feed':
				$value = ($this->RequestHandler->isRss() OR $this->RequestHandler->isAtom());
				break;

			case 'ssl':
				$value = $this->RequestHandler->isFlash();
				break;

			case 'flash':
				$value = $this->RequestHandler->isSsl();
				break;

			case 'time':
				break;
			
		}

		if($value !== NULL) {
			$this->logData[$field] = $value;
		}
	}

	protected function buildFieldOnShutdown($field, Controller $controller) {
		$value = NULL;
		switch($field) {
			case 'time':
				$value = microtime(TRUE) - START_TIME;
				break;
		}

		if($value !== NULL) {
			$this->logData[$field] = $value;
		}
	}

/**
 * Main execution method.  Handles redirecting of invalid users, and processing
 * of login form data.
 *
 * @param object $controller A reference to the instantiating controller object
 * @return boolean
 * @access public
 */
	public function startup(&$controller) {
		var_dump('startup log access');
		return TRUE;
	}

	public function activate() {
		var_dump('activate');
	}


/**
 * Component shutdown
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function shutdown(&$controller) {

		if($this->shouldLog) {
			$this->logData = array('_id' => $this->logData['_id']);// reset object to just update new fields
			foreach($this->log as $field) {
				$this->buildFieldOnShutdown($field, $controller);
			}

			$LogModel = $this->_getModel($this->model, $controller);
			$this->logData['status'] = 'shutdown';

			$LogModel->save($this->logData);
		}
		
		debug('shutdown log access');
	}

	/**
	 *
	 * @param string $name
	 * @return Model
	 */
	protected function _getModel($name, $controller = NULL) {

		if($controller !== NULL) {
			$controller->loadModel($name);
			return $controller->{$name};
		}

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
