<?php
	//GETTING VALUES
	if(isset($_POST["sentemail"])) {
		$sentemail = $_POST["sentemail"];
		$sentpassword = $_POST["sentpassword"];
	}
	else if(isset($_GET["sentemail"])) {
		$sentemail = $_GET["sentemail"];
		$sentpassword = $_GET["sentpassword"];
		
	}
	
	$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
	if ($mysqli->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
	
	//HASHING THE PASSWORD
	$sentpassword = hash("crc32",$sentpassword);
	
	$stmt = $mysqli->prepare("SELECT email, password FROM trackyourcampaignusers WHERE email = ?");
	$stmt->bind_param("s", $sentemail);
	$stmt->execute();
	$stmt->bind_result($email, $password);
	$stmt->fetch();

	$stmt->close();
	$mysqli->close();	

	if($email == null) {//RETURNING AN ERROR MESSAGE IF THE EMAIL ISN'T IN THE DATABASE
		echo json_encode("Unregistered email.");
	}
	else if($sentpassword != $password) {//RETURNING AN ERROR MESSAGE IF THE GIVEN PASSWORD ISN'T CORRECT
		echo json_encode("Incorrect password.");
	}
	else {//RETURNING OK IF THE PASSWORD AND EMAIL MATCH
		echo json_encode("OK");
	}
?>
