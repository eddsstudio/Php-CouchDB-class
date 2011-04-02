<?php
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	
	include('couchdb.php');
	
	$start = microtime();
	
	$db = new CouchDB('locations','localhost','5984','test','pass');
	
	//$c = $db->get('m1');
	
	//print_r($c);
	
	//$p = $db->put('m2','{"email":"tutz@inbox.lv"}');
	$new = array(
		'name'=>'bob',
		'surname'=>'jones',
		'email'=>'bob@mail.com',
		'group'=>'a'
	);
	
	//$p = $db->put('m3',$new);
	
	
	//$u = $db->update('emailcount',array('num'=>'7'));
	//print_r($u);  
	//echo $c->fields->c;
	
	//$v = $db->view('emails_groups','email_and_group');
	//print_r($v);
	
	// $s = $db->send('_design/emails_groups/_view/email_and_group',"GET");
	// print_r($s->getBody(true));

	
	
	$s = $db->send('_design/ciemi/_view/index?key="2152"',"GET");
	print_r($s->getBody(true));
	
	$end = microtime();

	echo 'Took:'.$end-$start.' seconds';
	
	
	
	