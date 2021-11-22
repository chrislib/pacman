<?php

	ini_set('upload_max_filesize', '40M');
	ini_set('post_max_size', '40M');
	ini_set('max_input_time', '300');
	ini_set('max_execution_time', '300');

	class dbh{
		private $name = 'db_name';
		private $usr = 'db_user';
		private $pwd = 'db_password';
		public $db;
		function __construct(){
			$this->db = new PDO("mysql:dbname=$this->name;host=localhost", $this->usr, $this->pwd);
			$this->db->exec("set names utf8");
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		}
		function query( $sql ){
			return $this->db->query( $sql );
		}
	}

	$db = new db();
	$page = 'main';
	if( isset($_GET['page']) && is_file('xml/'.$_GET['page'].'.xml') )
		$page = $_GET['page'];

	session_start();
	#print_r($_SESSION);
	
?>
