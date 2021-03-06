<?php
App::import('Model', 'Mongodb.MongodbModel');

class UserProfileBase extends MongodbModel{

	public $name	 = 'UserProfile';

	public $useTable = 'user_profiles';

	public $primaryKey = '_id';

	public $actsAs = array(
		'Mongodb.SqlCompatible',
	);
	/*
	var $mongoSchema = array(
		'data'=>array('type'=>'string'),
		'expires'=>array('type'=>'string'),
		'created'=>array('type'=>'datetime'),
		'modified'=>array('type'=>'datetime'),
	);
	//*/
	
	public $belongsTo = array(
		'User',
  );

	public $hasMany = array(
		
	);

	public $hasOne = array(
			
	);
}
