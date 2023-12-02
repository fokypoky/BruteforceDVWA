<?php

if( isset( $_GET[ 'Login' ] ) ) {
	// Get username
	$user = $_GET[ 'username' ];

	// Get password
	$pass = $_GET[ 'password' ];
	$pass = md5( $pass );

	// Check the database
	$query  = "SELECT * FROM `users` WHERE user = '$user' AND password = '$pass';";
	$result = mysqli_query($GLOBALS["___mysqli_ston"],  $query ) or die( '<pre>' . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)) . '</pre>' );
	
	$ip = $_SERVER["REMOTE_ADDR"];
	
	$visits_q = "SELECT count FROM visits WHERE ip = '$ip';";
	$connection = new mysqli("localhost", "dvwa", "p@ssw0rd", "dvwa");
	
	$visits_count = 0;

	if($qres = $connection->query($visits_q)){
		$rows_count = $qres->num_rows;
		if ($rows_count == 0) {
			$insert_q = "INSERT INTO visits(ip, count) VALUES('$ip', 1);";
			$connection->query($insert_q);
			$visits_count = 1;
		}
		else {
			foreach($qres as $row){
				$visits_count = $row["count"];
				break;
			}
		}
	}

	if ($visits_count > 3) {
		$html .= "<p>Too many requests!</p>";
	}

	else{
		if( $result && mysqli_num_rows( $result ) == 1 ) {
			// Get users details
			$row    = mysqli_fetch_assoc( $result );
			$avatar = $row["avatar"];
	
			// Login successful
			$html .= "<p>Welcome to the password protected area {$user}</p>";
			$html .= "<img src=\"{$avatar}\" />";
			$visits_count = 0;
		}
		else {
		//Login failed
			$html .= "<pre><br />Username and/or password incorrect.</pre>";
			$visits_count = $visits_count + 1;
		}
	
		
		$update_q = "UPDATE visits SET count = $visits_count WHERE ip = '$ip'";
		$connection->query($update_q);
	
		((is_null($___mysqli_res = mysqli_close($GLOBALS["___mysqli_ston"]))) ? false : $___mysqli_res);
	}
	}

?>
