<?php
App::import('Model', 'Mongodb.MongodbModel');

class UserGroupBase extends MongodbModel{

	public $name	 = 'UserGroup';

	public $useTable = 'user_groups';

	public $primaryKey = '_id';

	public $actsAs = array(
		'Mongodb.SqlCompatible',
	);
	/*
	var $mongoSchema = array(
		'identifier'=>array('type'=>'string'),
	  'name'=>array('type'=>'string'),
	  'desc'=>array('type'=>'string'),
		'haveRoles'=>array('type'=>'array'),
		'dontHaveRoles'=>array('type'=>'array'),
		'created'=>array('type'=>'datetime'),
		'modified'=>array('type'=>'datetime'),
	);
	//*/
	
	public $belongsTo = array(
		'User',
  );
}
