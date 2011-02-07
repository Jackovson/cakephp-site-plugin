<?php
app::import('Model', 'Mongodb.MongodbModel');

/**
 * For basic access loging and limiting
 * 
 */
class LogLimitBase extends MongodbModel {

	public $name	 = "LogRequest";

	public $useTable = 'log_requests';

	public $displayField = '_id';

	public $actsAs = array(
		'Mongodb.SqlCompatible',
		'Mongodb.Schemaless',
	);
	
	/*
	var $mongoSchema = array(
		'login'=>array('type'=>'string'),
		'password'=>array('type'=>'string'),
		'haveRoles'=>array('type'=>'array'),
		'dontHaveRoles'=>array('type'=>'array'),
		'created'=>array('type'=>'datetime'),
		'modified'=>array('type'=>'datetime'),
	);
	//*/

}
