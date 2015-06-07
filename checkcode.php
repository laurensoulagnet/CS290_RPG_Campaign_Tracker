<?php
	
	//GETTING SENT VALUES
	if(isset($_POST["sentcode"])) {
		$sentcode = $_POST["sentcode"];
	}
	else if(isset($_GET["sentcode"])) {
		$sentcode = $_GET["sentcode"];
		
	}
	
	$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
	if ($mysqli->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
	
	$sentcode = strtoupper($sentcode);
	
	//RETURNING TRUE IF THE SENT CODE IS ALREADY REGISTERED, FALSE OTHERWISE
	$stmt = $mysqli->prepare("SELECT code FROM trackyourcampaigncampaigns");
	$stmt->execute();
	$stmt->bind_result($code);
	$codeexists = "false";
	while($stmt->fetch()) {
		if ($code == $sentcode) {
			$codeexists = "true";
		}
	}

	$stmt->close();
	$mysqli->close();	
	
	echo json_encode($codeexists);
?>
