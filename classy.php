<?php
	class bitch{
		function __set($var, $val){
			$this->{$var} = $val;
		}
		function __get($var){
			return($this->{$var});
		}
	}

	# file handle for uploaded files
	#
	class filet extends bitch{
		const smax = 40000000;	# maximum size
		const typs = array(	# allowed file types
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
		);
		private $size, $err, $nfo, $tmp;
		public $name, $path, $ext;

		function __construct( $n,$f ){
			global $page;
			$this->name = $n;
			$this->size = $f['size'];
			$this->err = $f['error'];
			$this->tmp = $f['tmp_name'];

			if(!isset($this->err) || is_array($this->err) || is_object($this->err)){
				header("location:index.php?page=$page&msg=fail");
				exit;
			}
			if($this->err!=UPLOAD_ERR_OK){
				header("location:index.php?page=$page&msg=$this->err");
				exit;
			}
			if($this->size > self::smax){
				header("location:index.php?page=$page&msg=toobig");
				exit;
			}
			$this->nfo = new finfo(FILEINFO_MIME_TYPE);
			if( false === $this->ext = array_search( $this->nfo->file($f['tmp_name']), self::typs, true )){
				header("location:index.php?page=$page&msg=filetype");
				exit;
			}
			return $this;
		}
		function move( $path, $replace=true ){
			$dir = implode('/', array_slice( explode('/', $path), 0, -1 ));
			if( ! is_dir($dir) )
				return false;
			if( is_file($path) && !$replace )
				return false;
			if( ! move_uploaded_file( $this->tmp, $path ) )
				return false;
			return $this->$path = $path;
		}
	}

	# backend connection
	#
	class bexi extends dbh{
		protected $mi, $where, $orda;

		# db interface setup
		#
		function __construct($db=null, $mi=null, $where=null, $orda=null){

			if( !$db ) return $this;	# xml without db

			$this->db->tbl = $this->db->query("show tables from $db")->fetchall(PDO::FETCH_NUM);
			$this->src = $mi;

			if(strpos($mi,',')!==false)
				$this->tbl = explode(',', preg_replace('/,\s*/',',',$mi));
			else
				$this->tbl = [0=>$mi];
			
			$this->row = new bitch();
			$this->sql = '';

			foreach( $this->tbl as $i=>$t ){

				$col = $this->db->query("show columns from $t")->fetchall();
				foreach( $col as $j=>$c ){
					$this->row->{$t.'_'.$c->Field} = null;
					$this->sql .= "$t.$c->Field as $t"."_$c->Field" . ($j<count($col)-1?', ':'');
				}
				$this->sql .= ($i<count($this->tbl)-1?', ':'');
			}

			$this->max = current($this->db->query("select count(*) from $mi" . ($where?" where $where":"") . ($orda?" sort by $orda":""))->fetch()) - 1;
			$this->sql = "select $this->sql from $mi" . ($where?" where $where":"") . ($orda?" sort by $orda":"");
			#echo $this->sql;
			$this->row = $this->db->prepare($this->sql);
			$this->row->setFetchMode(PDO::FETCH_INTO, $this->row);
			$this->reset();

			#print_r($this);
			return $this;
		}

		# rewind db to where we started
		function reset(){
			$this->i = 0;
			$this->row->execute();
		}

		function __set($var, $val){
			$this->{$var} = $val;
		}
	}

	# command evaluation
	#
	class hexi extends bexi{

		# join our db interface on the xml
		#
		public function join($iu, $for=null){
			$this->vario($iu);

			# replace all <if ..> tags outside a for loop
			foreach( $iu->tag->if as $i=>$t ) if (!$for || strpos($t->_out, $for->_src) !== false || strpos($for->_out, $t->_src) === false ){
				$iu->_src = $this->cif($iu->_src,$t);
			}
			if (!$for ){
				return $iu->_src;
			}

			$for->tmp = $for->_out;
			while( $this->row->fetch() ){
				$for->_out = $for->tmp;

				# replace all <if ..> tags inside the for loop
				foreach( $iu->tag->if as $i=>$t ) {
					$for->_out = $this->cif( $for->_out, $t );
				}

				# replace all <db ..> tags inside the for loop
				foreach( $iu->tag->db as $i=>$t ) {
					$for->_out = $this->db( $for->_out, $t );
				}

				# replace all vars & db fields somewhere in the xml - like %tbl_col or %var
				preg_match_all( "/%\w+/", $for->_out, $r );
				foreach( $r[0] as $v ) if( isset($this->row->{substr($v,1)}) ){
					$for->_out = str_replace( $v, $this->row->{substr($v,1)}, $for->_out);
				}

				$iu->_src = str_replace($for->_src, "$for->_out\n$for->_src", $iu->_src);
				$this->i++;
			}

			$this->reset();
			$iu->_src = str_replace($for->_src, '', $iu->_src);
			return $iu->_src;
		}

		# <db ..> tag evaluation
		private function db( $src, $t ){
			if(isset($t->src) && isset($this->col->{$t->src})){
				$src = str_replace($t->_src, $this->row->{$t->src}, $src);
			}elseif(isset($t->tbl) && in_array($t->tbl, $this->tbl) && isset($t->col) && isset($this->row->{$t->tbl.'_'.$t->col})){
				$src = str_replace($t->_src, $this->row->{$t->tbl.'_'.$t->col}, $src);
			}
			return $src;
		}

		# <else> case evaluation
		private function elsa( $t ){
			if( strpos($t->_out,'<else>') === false )
				return null;
			preg_match( "/^(.*)\<else\>(.*)$/s", $t->_out, $r );
			if( substr_count($r[1],'<if ') == substr_count($r[1],'</if>') && substr_count($r[2],'<if ') == substr_count($r[2],'</if>'))
				return [$r[1], $r[2]];
			else
				return null;
		}

		# <if ..> tag evaluation
		private function cif( $src, $t ){
			if( isset($t->var) && isset($GLOBALS[$t->var]) && isset($t->key) && isset($GLOBALS[$t->var][$t->key]) ){
				$v = $GLOBALS[$t->var][$t->key];
			}elseif( isset($t->tbl) && !isset($this->row) ){
				return $src;
			}elseif( isset($t->tbl) && isset($t->col) && isset($this->row->{"$t->tbl"."_$t->col"}) ){
				$v = $this->row->{$t->tbl."_$t->col"};
			}else{
				$v = null;
			}
			$ja = true;
			if( isset($t->set) ){
				if( $t->set == 'y' && !isset($v) )
					$ja = false;
				if( $t->set == 'n' && isset($v) )
					$ja = false;
			}elseif( isset($t->val) && $t->val != $v ){
				$ja = false;
			}elseif( isset($t->min) || isset($t->max)){
				if( isset($t->min) && $t->min > $v )
					$ja = false;
				if( isset($t->max) && $t->max < $v )	
					$ja = false;
			}
			$r = $this->elsa($t);
			if($ja){
				if( is_array( $r ) ){
					$src = str_replace($t->_src, $r[0], $src);
				}else{
					$src = str_replace($t->_src, $t->_out, $src);
				}
			}else{
				if( is_array( $r ) ){
					$src = str_replace($t->_src, $r[1], $src);
				}else{
					$src = str_replace($t->_src, '', $src);
				}
			}
			return $src;
		}

		# <var ..> tag evaluation
		private function vario($iu){
			foreach( $iu->tag->var as $j=>$t ) {
				$iu->_src = str_replace($t->_src, $_GET[$t->name], $iu->_src);
			}
		}

	}

	class rexi{
		public $_src, $tag;

		# xml parser
		function __construct($mi){
			#echo "########$mi#########\n";
			$this->_src = file_get_contents("xml/$mi.xml");

			# instantly replace all we got in our $init-array
			preg_match_all( "/%\w+/", $this->_src, $r );
			foreach( $r[0] as $v ) if( isset($GLOBALS['init'][substr($v,1)]) ){
				$this->_src = str_replace( $v, $GLOBALS['init'][substr($v,1)], $this->_src );
			}

			$this->tag = new bitch();
			$this->taxi( 'def', $this->_src ); 

			# parse the defs out
			preg_match_all( "/%[\w\.]+/", $this->_src, $r );
			foreach( $r[0] as $v ) foreach($this->tag->def as $i=>$o) if( "$o->var." == substr($v,1) ){
				$o->val = str_replace(' ', '', $o->val);
				$a = explode( ',', $o->val );
				$i = 0;
				if( isset($_GET["$o->var"]) && is_numeric($_GET["$o->var"]) && count($a) ){
					$i = $_GET[$o->var];
					if( isset($_GET["$o->var>"]) && is_numeric($_GET["$o->var>"]) ){
						$i += $_GET["$o->var>"];
					}elseif( isset($_GET["$o->var<"]) && is_numeric($_GET["$o->var<"]) ){
						$i -= $_GET["$o->var<"];
					}
					if( $i >= count($a) ) $i = 0;
					elseif( $i < 0 ) $i = count($a) - 1;
					$vv = $a[$i];
				}else{
					$vv = $a[0];
				}
				$this->_src = str_replace( $v, $vv, $this->_src );
				$this->_src = str_replace( substr($v,0,-1), $i, $this->_src );
				$this->_src = str_replace( $o->_src, '', $this->_src );
			}

			$this->taxi( 'var', $this->_src ); 
			$this->taxi( 'db', $this->_src ); 
			$this->taxi( 'if', $this->_src ); 
			$this->taxi( 'for', $this->_src ); 
			#print_r($this);
		}

		# regex magic
		private function rex($tag, $src){
			#if($tag=='for')
				#$rex = "/(\<$tag .*?\>)(.*?)(\<\/$tag\>)/s";
			#else
			$rex = "/(\<$tag .*?\>)(.*)(\<\/$tag\>)/s";
			preg_match_all( $rex, $src, $r );
			if( !count( $r[0] ) ){
				preg_match_all("/\<$tag .*? \/\>/s", $src, $r);
				return $r[0];
			}
			$rr = preg_grep( $rex, $r[2] );
			if( !count( $rr ) ){
				return $this->fliparr($r);
			}else{
				# and yeah, it's even recursive (sorry bout that ;o)
				return array_merge( $this->fliparr($r), $this->rex($tag, $rr[0]) );
			}
		}

		# split tags into header, source 'code' for substitution and replacement (_out)
		# preceded by a _ just not to mess it up with attributes from the xml
		#
		private function taxi( $tag, $src ){
			$r = $this->rex( $tag, $src );
			$this->tag->{$tag} = [];
			foreach( $r as $rr ){

				$t = new bitch();	

				if( !is_array( $rr ) ){
					# means we got a self-closing tag
					$t->_src = $rr;
					$t->_hdr  = $rr;
				}else{
					# means we got an embracing tag
					$t->_src = $rr[0];
					$t->_hdr  = $rr[1];
					# gets us rid of ugly layout artifacts
					$t->_out = preg_replace( ["/^\n\s*/", "/\s*\n$/"], ['',''], $rr[2] );
				}

				# read in all those attributes
				preg_match_all( "/([^\>\" ]*?)\=\"([^\"]*?)\"/s", $t->_hdr, $s );
				foreach( $this->fliparr($s) as $i=>$a ){
					$t->{$a[1]} = $a[2];
				}

				$this->tag->{$tag}[] = $t;
			}
		}

		# flips - or better transposes - a 2D-array
		private function fliparr($array) {
			$out = array();
			foreach ($array as  $rowkey => $row) {
				foreach($row as $colkey => $col){
					$out[$colkey][$rowkey]=$col;
				}
			}
			return $out;
		}
	}

	# yet another db handle, somehow quite redundant with bexi (work in progress;)
	# 
	class db extends dbh{
		public $tbl, $col, $key, $id;
		private $row;
		function __construct( $tbl, $id=null ){
			$this->tbl = $tbl;
			$this->id = $id;
			$this->row = new bitch();
			$this->col = $this->db->query("show columns from $this->tbl")->fetchall();
			foreach( $this->col as $i=>$c ){
				if( isset($c->Key) && $c->Key ){
					$this->key = new bitch();
					$this->key->{$c->Field} = new bitch();
					$this->key->{$c->Field}->typ = $c->Key;
					if( is_object($this->id) && isset($this->id->{$c->Field}) ){
						$this->key->{$c->Field}->val = $this->id->{$c->Field};
					}elseif( /*$this->id &&*/ $c->Key == 'PRI' ){
						$this->key->{$c->Field}->val = $this->id;
					}elseif( is_null($this->id) && $c->Key == 'PRI' ){
						$this->key->{$c->Field}->val = null;
						$this->row->{$c->Field} = null;
					}
				}else{
					$this->row->{$c->Field} = null;
				}
			}
			#echo $this->sql('select');
			$this->row = $this->db->query($this->sql('select'))->fetch();
			#$this->$db->exec("select * from $this->tbl where ".$this->where());
			return $this;
		}
		function sql( $wot ){
			if( $wot == 'insert' ){
				$sql = "insert into $this->tbl("; 
				$i = 0;
				foreach( $this->row as $k=>$v ){
					$sql .= $k . (++$i<count((array)$this->row )?',':') values(');
				}
				$i = 0;
				foreach( $this->row as $k=>$v ){
					$sql .= "'$v'" . (++$i<count((array)$this->row )?',':')');
				}
			}elseif( $wot == 'update' ){
				$sql = "update $this->tbl set "; 
				$i = 0;
				foreach( $this->row as $k=>$v ){
					$sql .= "$k='$v'" . (++$i<count((array)$this->row )?',':'');
				}
			}elseif( $wot == 'select' ){
				$sql = "select "; 
				$i = 0;
				foreach( $this->row as $k=>$v ){
					$sql .= $k . (++$i<count((array)$this->row)?',':'');
				}
				$sql .= " from $this->tbl"; 
			}
			$wot = '';
			$i = 0;
			foreach( $this->key as $k=>$o ) if( $o->typ == 'PRI' && $o->val ){
				$wot .= "$k='$o->val'" . (++$i<count((array)$this->key)?' and ':'');
			}
			return $sql.($wot?" where $wot":'');
		}
		function __set($var, $val){
			if( $var == 'row' && is_array($val) ){
				$sql = 'insert';
				foreach( $val as $k=>$v ){
					#echo "$k:$v\n";
					if( $k == 'dst' ) continue;
					$r = explode('_',$k);
					if( count($r) <= 1 ) continue;
					if( $r[0] != $this->tbl ) continue;
					if( isset($this->key->{$r[1]}) && $this->key->{$r[1]}->typ == 'PRI' && $v ){
						$this->key->{$r[1]}->val = $v;
						$sql = 'update';
					}elseif( !isset($this->key->{$r[1]}) || $this->key->{$r[1]}->typ != 'PRI' ){
						$this->row->{$r[1]} = $v;
					}
				}
				$sql = $this->sql($sql);
				#echo "$sql\n";
				if( ! $n = $this->db->exec($sql) ){
					print_r( $this->db->errorInfo(), true );
					exit;
				}
				if( substr($sql,0,6) == 'insert' ){
					$this->id = $this->db->lastInsertId();
				}
			}
		}
		function __get($var){
			if( isset($this->{$var}) )
				return $this->{$var};
			#echo "var:$var\n";
			#echo "id:$this->id\n";
			#echo $this->sql('select');
			$this->row = $this->db->query($this->sql('select'))->fetch();
			#print_r($this->row);
			return($this->row->{$var});
		}
	}

?>
