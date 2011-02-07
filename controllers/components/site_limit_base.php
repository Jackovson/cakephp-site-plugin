<?php
//App::import('Model', 'Site.LogRequest');

/**
 * Log and check if a user doesent reach defined limits
 * 
 * Limits are defined for a given action and a element (a logged in user, a forum post, etc) . An action is a simple string identifier.
 * 
 * Example :
 * array(
 * 		'access' => array(
 * 			array(// mean : more than 10 try to do 'access' action by 10sec will fail
 *	 			'timeLimit' => 10,// in sec
 * 				'numberLimit' => 10,
 *			),
 *			// you can define more than one limit for each action
  * 		array(// mean : more than 100 try to do 'access' action by 60sec will ban the element for 1000 sec
 *	 			'timeLimit' => 60,// in sec
 * 				'numberLimit' => 100,
 * 				'banTime' => 1000,// in sec, if not specified action will just fail
 *			),
 * 		),
 * 		'forum:post' => array(
 * 	
 * 		),
 * 		'site:login' => array(
 * 	
 * 		),
 *	);
 *
 * Then eventually define actions related to a controller or an action.
 * 
 * Example :
 * $controllerActions = array('access');// each controller action (method) will check for 'access' action limits 
 * 
 * @TODO add handler parameter (using interface)
 * @TODO add possibility to define callback to limit reaching
 * 
 * 
 */
class SiteLimitBaseComponent extends Object {
	
	public $components = array('Auth', 'RequestHandler');

	protected $limitsBySessionEnabled = TRUE;// limit using sessions (only for sessions enabled and logged in users, not for others things) 
	protected $limitsWoSessionEnabled = TRUE;// limit using a backend to store users request and apply limits*
	
	protected $cleanProbability = 0.1;
	protected $cacheConfig = NULL;// see cache configs to use another config
	
	protected $limits = array();
	
	protected $allData  = NULL;

	/**
	 * Initializes AuthComponent for use in the controller
	 *
	 * @param object $controller A reference to the instantiating controller object
	 * @return void
	 * @access public
	 */
	public function initialize(&$controller, $settings = array()) {
		var_dump('initialize site limit');
		
		// check for :
		// getActionLimits() method @TODO
		// getControllerLimlits() method @TODO
		// actionLimits[action]
		// controllerLimits
		
		// get limits
		if(isset($controller->actionLimits[$controller->action])) {
			$settings['limits'] = $controller->actionLimits[$controller->action];
		} else if(isset($controller->controllerLimits)) {
			$settings['limits'] = $controller->controllerLimits;
		}
		
		// get actions
		$actions = array();
		if(isset($controller->actionsActions[$controller->action])) {
			$settings['actions'] = $controller->actionActions[$controller->action];
		} else if(isset($controller->controllerActions)) {
			$settings['actions'] = $controller->controllerActions;
		}
		
		$this->_set($settings);
		
		if(!empty($settings['limits']) AND !empty($settings['actions'])) {
			$this->doAction($settings['actions'], $this->Auth->user());
		}
	}
	
	/**
	 * Try to "do" an action
	 * 
	 * @throws SiteLimitException if an action limit is reached
	 *  
	 * @param mixed $actions string or array of actions identifiers
	 * @param array $element
	 * @param array $options
	 */
	public function doAction($actions, $element, array $options = array()) {
		
		if(is_array($actions) !== TRUE) {
			$actions = array($actions);
		}
		
		// check ban : if banned, stop here
		foreach($actions as $action) {
			$this->checkBan($action, $element);
		}
		
		// save actions (before checking) : is more restrictiv as a failed action will be logged as an succeeded one
		$this->addActions($actions, $element);
		
		// check every action (willl trhow an exception if a restriction is broken)
		foreach($actions as $action) {
			$this->checkAction($action, $element, $options);
		}
		
		// sometimes, clean data !
		if(rand(0, 1) < $this->cleanProbability) {
			$this->cleanData($element);
		}
		
		// and save all data
		$this->saveAllData($element);
	}
	
	/**
	 * Check an action for a user
	 * - try to call checkMyaction()
	 * - call genericCheckAction()
	 * 
	 * @throws UserActionPackageException in case of limit reached
	 *
	 * @param string $actionName
	 * @param array $eUser
	 */
	protected function checkAction($actionName, $element, array $option = array()) {
		
		// try to use a special method to check logs
		$methodName = 'check'.ucfirst($actionName);
		if(is_callable(array($this, $methodName)) === TRUE) {
			// this method should return FALSE if limit is reach, and have to call addBanishement() if needed
			$ok = $this->{$methodName}($actionName, $element, $option);
		} else {
			//use generic check with conf
			$ok = $this->genericCheckAction($actionName, $element, $option);
		}
		
		if(!$ok) {
			$this->saveAllData($element);// save data because we will throw an exception
			$this->failed($actionName, $element, $keyLimit, $option);
		} else {
			$this->success($actionName, $element);
		}
	}
	
	protected function genericCheckAction($actionName, $element, array $option = array()) {
		//get limits for this action
		$limits = $this->getLimits($actionName);
		
		//get logs for this action and element
		$actions = $this->getActionLogs($actionName, $element);
		
		//check it !
		foreach($limits as $keyLimit => $limit) {
			$nb = $this->count($actions, $limit['timeLimit']);
			
			if($nb > $limit['numberLimit']) {
				// action failed for this user...
				// check the banTime option 
				if(empty($limit['banTime']) === FALSE) {
					$this->addBanishement($actionName, $element, $limit['banTime']);
				}
				
				return FALSE;
			}
		}
		
		// action success !
		return TRUE;
	}
	
	protected function addBanishement($actionName, $element, $banTime) {
		//
		$all = $this->getAllData($element);
		
		$all['bans'][$actionName] = (time() + $banTime);
		
		$this->allData = $all;
	}
	
	/**
	 * Called when a user action failed because of limit reached
	 *
	 * @param string $actionName
	 * @param array $element
	 * @param string $keyLimit
	 */
	protected function failed($actionName, $element, $keyLimit, array $option = array()) {
		throw new SiteLimitException('Action#'.$actionName.' failed for key#'.$this->getKey($element).' on limit#'.$keyLimit.'. Try again later !', SiteLimitException::LIMIT_REACHED);	
	}
	
	/**
	 * Called when a user action failed because of banishement for this action
	 *
	 * @param string $actionName
	 * @param array $eUser
	 * @param int $bannedUntil
	 */
	protected function banned($actionName, $element, $bannedUntil, $time) {
		throw new SiteLimitException('Action#'.$actionName.' failed for key#'.$this->getKey($element).' because of banishement until '.date('Y-m-d H:i:s', $bannedUntil)." Current is: ".$time, SiteLimitException::BANNED);
	}

	/**
	 * Called when a user succed to perform an action
	 *
	 * @param string $actionName
	 * @param array $eUser
	 */
	protected function success($actionName, $element) {
		// nothiing !
	}
	
	/**
	 * Clean logs
	 *
	 * @param array $actions
	 * @param array $eUser
	 */
	protected function cleanData($element) {
		// FIXME
		// according to max time limit for the action, delete too old logs and bans
		$all = $this->getAllData($element);
		$bans = $all['bans'];
		$logs = $all['logs'];
		
		// check bans
		foreach ($bans as $actionName => $ban) {
			if($ban < time()) {
				unset($all['bans'][$actionName]);
			}
		}
		
		//check logs
		foreach ($logs as $logKey => $log) {
			
			// each log can be related to many actions...
			$actions = $log['actions'];
			$date = $log['date'];
			
			$time = time();
			
			// get actions we can delete according to their max limit
			foreach ($actions as $actionKey => $action) {
				if(($date + $this->getMaxTimeLimit($action)) < $time) {
					//remove this action because its date is to old according the max timeLimit
					unset($all['logs'][$logKey]['actions'][$actionKey]);
				}
			}
			
			// verifiy we have some actions in this log... if not, we can remove all this entry
			if(count($all['logs'][$logKey]['actions']) < 1) {
				unset($all['logs'][$logKey]);
			}
			
		}
		
		// save data
		$this->allData = $all;
	}
	
	/**
	 * Return the max timeLimit defined into ActionConf limits for an action
	 *
	 * @param string $actionName
	 * @return int
	 */
	protected function getMaxTimeLimit($actionName) {
		$limits = $this->getLimits($actionName);
		$maxLimit = 0;
		foreach($limits as $limit) {
			if($limit['timeLimit'] > $maxLimit) {
				$maxLimit = $limit['timeLimit'];
			}
		}
		return $maxLimit;
	}
	
	/**
	 * Return logs related to an action
	 *
	 * @param string $actionName
	 * @return array
	 */
	protected function getActionLogs($actionName, $element) {
		
		$logs = $this->getAllLogs($element);
		
		$actions = array();
		foreach($logs as $log) {
			if(
				array_search($actionName, $log['actions'], TRUE) !== FALSE
			) {
				$actions[] = array('date' => $log['date']);
			}
		}
		
		return $actions;
	}
	
	/**
	 * Return limits defined into ActionConf for an action
	 *
	 * @param string $actionName
	 * @return array
	 */
	protected function getLimits($actionName) {
		return $this->limits[$actionName];
	}
	
	/**
	 * count actions done during a time limit
	 *
	 * @param array $actions
	 * @param int $timeLimit
	 * @return int
	 */
	protected function count($actions, $timeLimit) {
		$nb = 0;
		foreach($actions as $key => $action) {
			if( (time() - $timeLimit ) < $action['date']) {
				$nb++;
			}
		}
		return $nb;
	}
	
	/**
	 * Check bans for an element
	 *
	 * @throws SiteLimitException
	 * @param $actionName
	 * @param $eUser
	 */
	protected function checkBan($actionName, $element) {
		$bans = $this->getAllBans($element);
		$time = time();
		if(
			empty($bans[$actionName]) === FALSE AND// ban entry exist for this action
			$bans[$actionName] >= $time
		) {
			$this->banned($actionName, $element, $bans[$actionName], $time);
		}
	}
	
	/**
	 * Return all bans for this user
	 *
	 * @return array
	 */
	protected function getAllBans($element) {
		$allData = $this->getAllData($element);
		return $allData['bans'];
	}
	
	/**
	 * Return all logs for this element
	 *
	 * @return array
	 */
	protected function getAllLogs($element) {
		$allData = $this->getAllData($element);
		return $allData['logs'];
	}
	
	protected function getAllData($element) {
		
		if($this->allData !== NULL) {
			return $this->allData;
		}
		
		$this->allData = Cache::read($this->getKey($element));
		if($this->allData === FALSE) {
			$this->allData = array('logs' => array(), 'bans' => array());
		}
		if(empty($this->allData['logs'])) {
			$this->allData['logs'] = array();
		}
		if(empty($this->allData['bans'])) {
			$this->allData['bans'] = array();
		}
		
		return $this->allData;
	}
	
	protected function saveAllData($element) {
		if($this->allData !== NULL) {
			Cache::write($this->getKey($element), $this->allData);
		}
	}
	
	protected function getKey($element) {
		// @TODO improve key building
		$key = 'limit';
		if(!empty($element['User']['_id'])) {
			$key .= '_user:'.$element['User']['_id'];
		}
		$key .= '_ip:'.$this->RequestHandler->getClientIp(TRUE);
		
		return $key;
	}
	
	protected function addActions(array $actions, $element) {
		//
		$all = $this->getAllData($element);
		
		$newLog = array();
		$newLog['date'] = time();
		$newLog['actions'] = $actions;
		
		$all['logs'][] = $newLog;
		
		$this->allData = $all;
	}
	
	protected function storeAllData($data, array $element) {
		Cache::write($this->getKey($element), $data);
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
		var_dump('startup limits');
		return TRUE;
	}


/**
 * Component shutdown
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function shutdown(&$controller) {

		debug('shutdown limits');
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


class SiteLimitBaseException extends Exception {
	
	const LIMIT_REACHED = 1;
	const BANNED = 2;
	
	protected $action;

	public function __construct($message, $code = NULL) {
		$message = '[SiteLimit] '.$message;
		parent::__construct($message, $code);
	}

}
