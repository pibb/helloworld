<?php
	require_once("library/webpage.php");
	Webpage::self();
?>

<form action="?" method="GET">
	<label for="hash_test">Enter Hash</label><input id="hash_test" type="text" name="detect"/><button name="type" value="detect" >Detect</button>
	<br />
	<label for="hash_test">Enter Term</label><input id="hash_test" type="text" name="hash"/><button name="type" value="create" >Create Hashes</button>
</form>
<hr />

<?php
	
	$type =  isset( $_GET['type'] ) ?  $_GET['type'] : null;
	$hash =  isset( $_GET['hash'] ) ?  $_GET['hash'] : null;
	$detect =  isset( $_GET['detect'] ) ?  $_GET['detect'] : null;
	
	if ( $type == "create" ) {

			echo "<h1>Hash of \"$hash\"</h1>\n";
			echo "<ul class=\"hash\">\n";
			echo "\t<li><span class=\"hash md5\">MD5</span> ".md5($hash)."</li>\n";
			echo "\t<li><span class=\"hash sha1\">SHA1</span> ".sha1($hash)."</li>\n";
			
			foreach (hash_algos() as $v) {
					echo "\t<li><span class=\"hash\">$v</span> ".hash($v, $hash)."</li>\n";
			} 
			
			echo "</ul>\n";
		

		

	}
	
?>