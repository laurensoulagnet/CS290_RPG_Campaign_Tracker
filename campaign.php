<?php
	session_start();
	if(!isset($_SESSION["email"])) {//REDIRECTING THE USER TO THE THE LOG IN PAGE IF THE USER HAS YET TO LOG IN
		header("Location: index.html"); 
		exit;
	}
	
	//GENERATES THE FOUR LETTER CODE USERS USE TO SHARE THE CAMPAIGN, CHECKING THAT IT DOES NOT ALREADY EXIST
	function generatecode() {
		for($i=0; $i<4; $i++){ 
			$code .= chr(rand(0,25)+65); 
		}
		$allcodes = array();
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		$stmt = $mysqli->prepare("SELECT code FROM trackyourcampaigncampaigns");
		$stmt->execute();
		$stmt->bind_result($acode);
		while($stmt->fetch()) {
			$allcodes[] = $acode;
		}
		$stmt->close();
		$mysqli->close();
		
		while(in_array($code, $allcodes)) {
			for($i=0; $i<4; $i++){ 
				$code .= chr(rand(0,25)+65); 
			}
		}
		$code = strtoupper($code);
		return $code;
	}
	
	if(isset($_POST["newcampaignname"])) {//IF STARTING A NEW CAMPAIGN, GENERATING A CODE AND ADDING THE INFORMATION TO THE DATABASE
		$code = generatecode();
		$lastedited = date("F j, Y, g:i a");
		$name = $_POST["newcampaignname"];
		
		
		//ADDING THE NEW CAMPAIGN TO THE CAMPAIGN DATABASE
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		
		$stmt = $mysqli->prepare("INSERT INTO trackyourcampaigncampaigns(code, name, lastedited) VALUES (?, ?, ?)");
		$stmt->bind_param("sss", $code, $name, $lastedited);
		$stmt->execute();
		
		
		//ADD NEWLY CREATED CAMPAIGN TO THE USER'S LIST OF CAMPAIGNS
		$email = $_SESSION["email"];
		
		$stmt = $mysqli->prepare("SELECT campaigns FROM trackyourcampaignusers WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->bind_result($campaigns);
		$stmt->fetch();
		
		$stmt->close();
		$mysqli->close();
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}

		if ($campaigns != "") {
			$campaigns = explode(", ", $campaigns);
		} else {
			$campaigns = array();
		}
		$campaigns[] = $code;
		$updatedcampaigns = implode(", ", $campaigns);
		
		$stmt = $mysqli->prepare("UPDATE trackyourcampaignusers SET campaigns = ? WHERE email = ?");
		$stmt->bind_param("ss", $updatedcampaigns, $email);
		$stmt->execute();
		$stmt->close();
		$mysqli->close();
	}
	else if(isset($_POST["neweventcampaign"])) {//IF THE USER IS ADDING AN EVENT, ADDING THAT TO THE DATABASE
		
		//GETTING THE VALUES SENT
		$newcampaign = $_POST["neweventcampaign"];
		$newname = $_POST["neweventname"];
		$newdateplayed = $_POST["neweventdate"];
		$newdescription = $_POST["neweventdescription"];
		
		$newpcs = array();
		$newnpcs = array();
		$sizeofcharacters = sizeof($_POST["neweventcharacter"]);

		for ($i=0; $i<$sizeofcharacters; $i++) {
			
		   if(strcmp($_POST["neweventcharacterstatus"][$i], "NPC") == 0) {
			   $newnpcs[] = $_POST["neweventcharacter"][$i];
		   } 
		   else {
			   $newpcs[] = $_POST["neweventcharacter"][$i];
		   }
		}
		
		$newpcs = implode(", ", $newpcs);
		$newnpcs = implode(", ", $newnpcs);
		
		$code = $newcampaign;
		$lastplayed = $newdateplayed;
		
		//INSERTING THE NEW VALUES INTO THE CAMPAIGN DATABASE
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		
		if(is_null($newnpcs)) {
			$newnpcs = " ";
		}
		if(is_null($newpcs)) {
			$newpcs = " ";
		}
		if(is_null($newdescription)) {
			$newdescription = " ";
		}
		
		$stmt = $mysqli->prepare("INSERT INTO trackyourcampaignevents(campaign, name, pcs, npcs, dateplayed, description) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("ssssss", $newcampaign, $newname, $newpcs, $newnpcs, $newdateplayed, $newdescription);
		$stmt->execute();
		$stmt->close();
		
		//UPDATING CAMPAIGN LAST EDITED DATE
		$lastedited = date("F j, Y, g:i a");
		$stmt = $mysqli->prepare("UPDATE trackyourcampaigncampaigns SET lastedited = ? WHERE code = ?");
		$stmt->bind_param("ss", $lastedited, $newcampaign);
		$stmt->execute();
		$stmt->close();
		
		//GETTING CURRENT CAMPAIN INFO
		$stmt = $mysqli->prepare("SELECT code, name, lastedited FROM trackyourcampaigncampaigns WHERE code = ?");
		$stmt->bind_param("s", $newcampaign);
		$stmt->bind_result($code, $name, $lastedited);
		$stmt->execute();
		$stmt->fetch();
		$stmt->close();
		$mysqli->close();	
	}
	else if(!isset($_GET["campaign"]) && !isset($_POST["neweventcampaign"])) {//IF THE USER IS JUST GOING TO THE BASE PAGE FOR NO REASON, REDIRECTING THEM TO THEIR CAMPAIGN PAGE
		header("Location: mycampaigns.php"); 
		exit;
	}
	else {//IF THE USER IS JUST GOING TO A SPECIFIC CAMPAIGN'S PAGE
		$code = $_GET["campaign"];
		
		//MAKING SURE THAT CODE IS ALREADY REGISTERED, REDIRECTING IF NOT
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		
		$stmt = $mysqli->prepare("SELECT code FROM trackyourcampaigncampaigns");
		$stmt->execute();
		$stmt->bind_result($allcode);
		$codeexists = "false";
		while($stmt->fetch()) {
			if ($code == $allcode) {
				$codeexists = "true";
			}
		}

		$stmt->close();
		$mysqli->close();	

		if ($codeexists == "false") {
			header("Location: mycampaigns.php"); 
			exit;
		}
		
		//IF THE CAMPAIGN EXISTS
		$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
		if ($mysqli->connect_errno){
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		$stmt = $mysqli->prepare("SELECT name, lastedited FROM trackyourcampaigncampaigns WHERE code = ?");
		$stmt->bind_param("s", $code);
		$stmt->bind_result($name, $lastedited);
		$stmt->execute();
		$stmt->fetch();
		$stmt->close();
		$mysqli->close();		
	}
	
	//GETTING EVENTS ASSOCIATED WITH THE CURRENT CAMPAIGN
	$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "millerla-db", "y66hx5HBu25Cgt30", "millerla-db");
	if ($mysqli->connect_errno){
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
	$stmt = $mysqli->prepare("SELECT name, pcs, npcs, dateplayed, description FROM trackyourcampaignevents WHERE campaign = ? ORDER BY dateplayed");
	$stmt->bind_param("s", $code);
	
	
	$stmt->bind_result($eventname, $eventpcs, $eventnpcs, $eventdateplayed, $eventdescription);
	$stmt->execute();
	
	$i = 0;
	$events = array();
	while($stmt->fetch()) {
		
		$events[$i] = array();
		$events[$i]["name"] = $eventname;
		$events[$i]["pcs"] = $eventpcs;
		$events[$i]["npcs"] = $eventnpcs;
		$events[$i]["dateplayed"] = $eventdateplayed;
		$events[$i]["description"] = $eventdescription;
		$i++;
	}

	$numberofevents = $i;
	$stmt->close();
	$mysqli->close();		
	
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
	<title> <?php echo $name; ?> </title>
	<script src="vis/dist/vis.js"></script>
	<link href="vis/dist/vis.css" rel="stylesheet" type="text/css" />
	<link rel = "stylesheet" href = "trackyourcampaign.css">
	<script type="text/javascript">
		
		//PRINTS ADDITIONAL INPUT AREAS, SO THE USER CAN ENTER THE NAME OF MORE THAN ONE CHARACTER AND WHETHER THEY ARE NPCS OR PCS
		function addcharacter() {
			var newcharacterarea = document.getElementsByName("newcharacterarea"); 
			newcharacterarea[0].innerHTML;
			
			var characterlabel = document.createElement("label");
			characterlabel.setAttribute("class", "shiftright");
			var ctext = document.createTextNode("Character: ");
			characterlabel.appendChild(ctext);
			newcharacterarea[0].appendChild(characterlabel);
			
			var nameinput = document.createElement("input"); 
			nameinput.setAttribute("type","text");
			nameinput.setAttribute("name","neweventcharacter[]");
			newcharacterarea[0].appendChild(nameinput);				
			
			var npcinput = document.createElement("input"); 
			npcinput.setAttribute("type","checkbox");
			npcinput.setAttribute("value","NPC");
			npcinput.setAttribute("name","neweventcharacterstatus[]");
			newcharacterarea[0].appendChild(npcinput);
			
			var npclabel = document.createTextNode("NPC");
			newcharacterarea[0].appendChild(npclabel);
			
			var breakelement = document.createElement('br'); 
			newcharacterarea[0].appendChild(breakelement);
		}
		
		//PRINTING THE INFORMATION ABOUT A GIVEN EVENT
		function printevent(eventid){
			
			var eventarray = <?php echo json_encode($events); ?>;

			var eventarea = document.getElementById("eventcontent"); 
			
			var h2 = document.createElement("H2");
			var thename = document.createTextNode(eventarray[eventid].name);
			h2.appendChild(thename);  

			var pclabel = document.createTextNode("PCs: ");
			var thepcs = document.createTextNode(eventarray[eventid].pcs);
			var npclabel = document.createTextNode("NPCs: ");
			var thenpcs = document.createTextNode(eventarray[eventid].npcs);
			var descriptionlabel = document.createTextNode("Description: ");	
			var thedescription = document.createTextNode(eventarray[eventid].description);
			
			var i;
			for (i = eventarea.childNodes.length - 1; i >= 0; i--) {
				eventarea.removeChild(eventarea.childNodes[i]);
			}
			
			eventarea.appendChild(h2);
			eventarea.appendChild(pclabel);
			eventarea.appendChild(thepcs);
			eventarea.appendChild(document.createElement('br'));
			eventarea.appendChild(npclabel);
			eventarea.appendChild(thenpcs);
			eventarea.appendChild(document.createElement('br'));
			eventarea.appendChild(descriptionlabel);
			eventarea.appendChild(thedescription);
			eventarea.appendChild(document.createElement('br'));

		}
		
		//IF THE "neweventcharacterstatus[]" BOX IS NOT CHECKED, RECORDING THE VALUE STILL, BUT WITH THE VALUE OF "PC"
		function changecharactersstatus() {
			var statuses = document.getElementsByName("neweventcharacterstatus[]"); 
			for(var i = 0; i < statuses.length; i++) {
				if(statuses[i].checked == false) {
					statuses[i].value = "PC";
					statuses[i].checked = true;
				}
			}
		}
	</script>
  </head>
  <body>
		
	<div class = "header">
		<div class = "headercontent">
		<h1 id = "pageheadertitle"><?php echo $name; ?> </h1>
		<p id = "pageheaderlogout"><?php printheaderlogout(); ?></p>
		</div>
	</div>

	<h2> Timeline </h2>
	<div id = "timelinecontent"> </div>
	
	<script type="text/javascript">
		//PRINTING OUT THE TIMELINE
		//note: this wasn't working when the script was above the div
		
		var container = document.getElementById('timelinecontent');
		var items = new vis.DataSet([
		<?php 
			for($j = 0; $j < $numberofevents; $j++)
			{
			
				echo "{ id: ".$j.", content: '".$events[$j]['name']."', start: '".$events[$j]['dateplayed']."'},";
			}
		?>
		]);

		var options = { width: '70%',};
		var timeline = new vis.Timeline(container, items, options);

		timeline.on('select', function (properties) {//PRINTING THE INFORMATION ABOUT AN EVENT WHEN THE USER CLICKS ON ITS TIMELINE EVENT
			var eventobject = timeline.getEventProperties(event)
			printevent(eventobject.item);
		});		
	</script>

	<div id = "eventcontent" class = "content"> 
		<p> Click on an event to see the details. </p>
	</div>

	<h2> Record a New Event </h2>
	
	<form name="neweventform" action="campaign.php" method="post" onsubmit="return changecharactersstatus()">
	<input type="hidden" name="neweventcampaign" value="<?php echo $code; ?>">
	<label for="neweventname"> Event Title: </label> <input type="text" name="neweventname" required> <br>
	<div name = "newcharacterarea"></div>
	<a onclick="addcharacter()"> Add Another Character </a> <br>
	<label for="neweventdescription"> Description: </label> <input type="text" name="neweventdescription"> <br>
	<label for="neweventdate"> Date Played: </label> <input type="date" name="neweventdate" required> <br>
	<input type="submit" value="Record" class = "addeventbutton">
	</form>
	
	<h2> Share This Campaign </h2>
	<p> Have your friends follow this campaign by entering the share code "<?php echo $code; ?>".

  </body>
  <script> addcharacter(); //SO ALL OF THE ADD CHARACTER SECTIONS ARE PRINTED THE SAME WAY</script>
</html>
