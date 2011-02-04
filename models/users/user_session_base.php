<?php

class UserSession extends AppModel{

	public $name	 = 'Session';

	public $useTable = 'cake_cessions';

	public $primaryKey = '_id';

	public $displayField = 'login';

	public $actsAs = array(
		'Mongodb.SqlCompatible',
	);

	var $mongoSchema = array(
		'data'=>array('type'=>'string'),
		'expires'=>array('type'=>'string'),
		'created'=>array('type'=>'datetime'),
		'modified'=>array('type'=>'datetime'),
	);

	public $belongsTo = array(
  );

	public $hasMany = array(
		
	);

	public $hasOne = array(
			
	);
}
