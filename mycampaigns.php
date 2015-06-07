<?php
	session_start();
	if (isset($_GET["logout"])) {//IF THE USER HAS CHOSEN TO LOG OUT, REDIRECTING THEM TO THE LOG IN PAGE
		session_destroy();
		header("Location: index.html"); 
		exit;
	}
	else if(isset($_POST["loginemail"])) {//SAVING THE EMAIL IF THE USER HAS JUST LOGGED IN
		$_SESSION["email"] = $_POST["loginemail"];
	}
	else if(isset($_POST["signupemail"])) {//SAVING THE EMAIL IF THE USER HAS JUST SIGNED UP
		$_SESSION["email"] = $_POST["signupemail"];
	}
	if(!isset($_SESSION["email"])) {//IF THE USER HAS YET TO LOG IN, REDIRECTING THEM TO THE LOG IN PAGE
		header("Location: index.html"); 
		exit;
	}
	
	//GETTING THE CURRENT USER
	$email = $_SESSION["email"];
	
	//GETTING THE CAMPAIGNS THE USER FOLLOWS
	$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
	if ($mysqli->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
	$stmt = $mysqli->prepare("SELECT campaigns FROM trackyourcampaignusers WHERE email = ?");
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$stmt->bind_result($campaigns);
	$stmt->fetch();
	$stmt->close();
	
	if ($campaigns != "") {
	$campaigns = explode(", ", $campaigns);
	} else {
		$campaigns = array();
	}

	//IF USER HAS SENT DELETE INFORMATION, THE SENT CAMPAIGN CODE IS REMOVED FROM ONES THE USER FOLLOWS
	if(isset($_POST["delete"])) {
		$campaigntodelete = $_POST["delete"];
		
		$key = array_search($campaigntodelete,$campaigns);
		unset($campaigns[$key]);
		$campaigns = array_values($campaigns);
		
		$updatedcampaigns = implode(", ", $campaigns);
		
		$stmt = $mysqli->prepare("UPDATE trackyourcampaignusers SET campaigns = ? WHERE email = ?");
		$stmt->bind_param("ss", $updatedcampaigns, $email);
		$stmt->execute();
		$stmt->fetch();
		$stmt->close();
		
	}
	
	//IF USER WANTS TO FOLLOW NEW CAMPAIGN, ITS CODE IS ADDED TO THEIR ARRAY OF FOLLOWED CAMPAIGNS
	if(isset($_POST["campaigncode"])) {
		$campaigntoadd = $_POST["campaigncode"];
		$campaigntoadd = strtoupper($campaigntoadd);
		$campaigns[] = $campaigntoadd;
		$campaigns = array_unique($campaigns);
		$updatedcampaigns = implode(", ", $campaigns);
		$stmt = $mysqli->prepare("UPDATE trackyourcampaignusers SET campaigns = ? WHERE email = ?");
		$stmt->bind_param("ss", $updatedcampaigns, $email);
		$stmt->execute();
		$stmt->fetch();
		$stmt->close();
	}
	$mysqli->close();
	
	function printcampaigntable() {
	//PRINTING THE CAMPAIGNS THE USER IS FOLLOWING, THEIR NAME, THE DATE THEY WERE LAST EDITED, AND AN UNFOLLOW BUTTON FOR EACH		
		$email = $_SESSION["email"];

		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		$stmt = $mysqli->prepare("SELECT campaigns FROM trackyourcampaignusers WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->bind_result($campaigns);
		$stmt->fetch();
		$stmt->close();

		if ($campaigns != "") {
		$campaigns = explode(", ", $campaigns);
		
		$mysqli->close();
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		
		echo "
		<table>
		<thead> 
        <tr> <th> Name <th> Last Edited <th>
		</thead>
		<tbody>";
		
		$stmt = $mysqli->prepare("SELECT name, lastedited FROM trackyourcampaigncampaigns WHERE code = ?");
		$stmt->bind_param("s", $currentcampaign);
	
		foreach($campaigns as $currentcampaign) {
			
			$stmt->execute();
			$stmt->bind_result($currentname, $currentlastedited);
			$stmt->fetch();
			echo "<tr> <td> <a href = campaign.php?campaign=".strtoupper($currentcampaign)."> ".$currentname."</a> <td> ".$currentlastedited." <td>  ";
			echo '<form action = "mycampaigns.php" method = "post" id = "unfollow">
			<input type = "hidden" name = "delete" value = "'.$currentcampaign.'">
			<button type = "submit" class = "unfollowbutton"> Unfollow </button> </form>';
		}
		
		
		$stmt->close();
		$mysqli->close();
		echo '</tbody> </table>';
		
		}
		else {
			echo "<p> Enter a four letter campaign code to join an existing campaign or create you own below. </p>";
		}
		
	}
	
	//PRINTING OUT THE USER NAME AND A LOG OUT LINK
	function printheaderlogout() {
		echo "<a href = mycampaigns.php>". $_SESSION["email"] ."</a><br>";
		echo "<a href = mycampaigns.php?logout=true> Log Out </a>";
	}
	
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset = "utf-8">
    <title> My Campaigns </title>
	<link rel = "stylesheet" href = "trackyourcampaign.css">
	<script type="text/javascript">
	
	//TURNS PARAMETERS INTO A URL STRING- copied from demo.js featured in the Week 4: JavaScript Part 2 video "Ajax"
	function urlStringify(obj) { 
		var str = [];
		var prop;
		var s;
		for (prop in obj) {
			s = encodeURIComponent(prop) + '=' + encodeURIComponent(obj[prop]);
			str.push(s);
		}
		return str.join('&');
	}

	//	CHECKING WHETHER A THE CODE FOR A CAMPAIGN THE USER WANTS TO FOLLOW IS ALREADY REGISTERED, AND PRINTING AN ERROR MESSAGE IF NOT
	function checkcode() {

		var thecode = document.getElementsByName("campaigncode")[0].value;
		var checkcodereturned;
		
		var checkcoderequest = new XMLHttpRequest();
		var params = {
			sentcode:thecode
		};
		var url = "checkcode.php";
		url += '?' + urlStringify(params);
		
		checkcoderequest.onreadystatechange = function() {
			if (this.readyState === 4 && this.status==200) {
			
				checkcodereturned = this.responseText; 
				checkcodereturned = JSON.parse(checkcodereturned);


				if (checkcodereturned == "false") {
					document.getElementById("followerrors").innerHTML = "";
					var errormessage = document.createElement("p");
					var etext = document.createTextNode("That campaign does not exist.");
					errormessage.appendChild(etext);
					document.getElementById("followerrors").appendChild(errormessage); 

				} else 
					document.followacampaignform.submit();
			} 
		}; 

		var check = checkcoderequest.open('POST', url);
		checkcoderequest.send();
					
		return false;

	}
</script>
  </head>
  <body>
	
	<div class = "header">
		<div class = "headercontent">
		<h1 id = "pageheadertitle"> My Campaigns </h1>
		<p id = "pageheaderlogout"><?php printheaderlogout(); ?></p>
		</div>
	</div>
	
	<h2> Followed Campaigns </h2>
	<?php  printcampaigntable(); ?>
	
	<h2> Follow a Campaign </h2>
	<p> To follow a campaign, enter it's four letter share code, (e.g. ASDF). </p>
	<div id = "followerrors" class= "errorsection"></div>
	<form name="followacampaignform" action="mycampaigns.php" onsubmit="return checkcode()" method="post">
	<label for="campaigncode"> Campaign Code: </label> <input type="text" name="campaigncode">
	<input type="submit" value="Follow">
	</form>
	
	<h2> Start a New Campaign </h2>
	<p> Enter the title of your new campaign, (e.g. Karen's Campaign or First Era Adventures) </p>

		<form name="startanewcampaignform" action="campaign.php" method="post">
		<label for="newcampaignname"> Campaign Name: </label> <input type="text" name="newcampaignname">
		<input type="submit" value="Create">
		</form>

  </body>

</html>
