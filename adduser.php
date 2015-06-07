<?php
	//GETTING THE SENT VALUES
	if(isset($_POST["sentemail"])) {
		$email = $_POST["sentemail"];
		$password = $_POST["sentpassword"];
	}
	else if(isset($_GET["sentemail"])) {
		$email = $_GET["sentemail"];
		$password = $_GET["sentpassword"];
	}

	//CHECKING IF EMAIL IS ALREADY REGISTERED
	$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
	if ($mysqli->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}

	$stmt = $mysqli->prepare("SELECT email FROM trackyourcampaignusers WHERE email = ?");//GETTING ALL OF THE EMAILS
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$stmt->bind_result($receivedemail);
	$stmt->fetch();

	$stmt->close();
	$mysqli->close();	

	if($receivedemail != null) {//RETURNING AN ERROR MESSAGE IF THE EMAIL ALREADY EXISTS IN THE DATABASE
		echo json_encode("Email already registered.");
	}
	else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {//RETURNING AN ERROR MESSAGE IF THE SENT EMAIL VALUE IS NOT ACTUALLY AN EMAIL
		echo json_encode("Not an email.");
	}
	else {//ADDING THE USER NAME AND PASSWORD TO THE DATABASE IF NOT ALREADY REGISTERED AND THE EMAIL IS IN THE CORRECT FORMAT
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		
		$password = hash("crc32",$password);//HASHING THE PASSWORD
		
		$stmt = $mysqli->prepare("INSERT INTO trackyourcampaignusers(email, password) VALUES (?, ?)");
		$stmt->bind_param("ss", $email, $password);
		$stmt->execute();
		$stmt->close();
		$mysqli->close();
		
		echo json_encode("OK");//RETURNING OK IF THE USER HAS BEEN ADDED
	}
	
?>
