<?php
app::import('Model', 'Mongodb.MongodbModel');

abstract class UserBase extends MongodbModel {

	public $name = "User";

	public $useTable = 'users';

	public $displayField = 'login';

	public $actsAs = array(
		'Mongodb.SqlCompatible',
		'Mongodb.Schemaless',
		'Site.SimpleCredentialable',
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


	public $hasMany = array(
		'Directories' => array(
			'className' => 'UserDirectory',
			'foreignKey' => 'user_id',
			'reverse' => 'Owner',
		)
	);
	
	public $belongsTo = array(
		'LivingCity' => array(
			'className' => 'City',
			'foreignKey' => 'city_id',
			'reverse' => 'Inhabitants',
		),
	);
	
	public $hasOne = array(
		'Profile' => array(
			'className' => 'UserProfile',
			'foreignKey' => 'user_id',
			'reverse' => 'Owner'
		),
	);

	public $hasList = array(
		'Photo',
		'UserGroups' => array('className' => 'UserGroup'),
	);
}
