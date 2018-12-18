<!DOCTYPE html>
<html>
<style>
input[type=text], select {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

input[type=submit] {
    width: 100%;
    background-color: #4CAF50;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

input[type=submit]:hover {
    background-color: #45a049;
}
input.textInput {
    width:50%;
}

div {
    margin: auto;
    width:100%;
    padding: 3px;
}

.main {
    border-radius: 5px;
    margin: auto;
    width:500px;
    background-color: #f2f2f2;
    padding: 20px;
}

.slidecontainer {
    width: 100%;
}

p {
    font-size: 100%;
} 

p.sub {
    font-size: 75%;
}  

</style>
<script type="text/javascript">
function yesnoCheck() {
    if (document.getElementById('noCheck').checked) {
        //document.getElementById('rmodellist').style.display = 'block';
        //document.getElementById('nnmodellist').style.display = 'block';
	typeCheck();
    } else if (document.getElementById('yesCheck').checked) {
        document.getElementById('rmodellist').style.display = 'none';
        document.getElementById('nnmodellist').style.display = 'none';
	if(document.getElementById('neuralnetwork').checked) {
        	document.getElementById('statlist').style.display = 'block';
		document.getElementById('dataanalysis').style.display = 'block';	
	} else if (document.getElementById('lregression').checked)
		{document.getElementById('statlist').style.display = 'none';
		document.getElementById('dataanalysis').style.display = 'none';
	}
    } else {}
}
function typeCheck() {
    if (document.getElementById('neuralnetwork').checked && document.getElementById('noCheck').checked) {
        document.getElementById('nnmodellist').style.display = 'block';
        document.getElementById('rmodellist').style.display = 'none';
	document.getElementById('statlist').style.display = 'none';
	document.getElementById('dataanalysis').style.display = 'none';
    } else if (document.getElementById('lregression').checked && document.getElementById('noCheck').checked) {
        document.getElementById('rmodellist').style.display = 'block';
        document.getElementById('nnmodellist').style.display = 'none';
	document.getElementById('statlist').style.display = 'none';
	document.getElementById('dataanalysis').style.display = 'none';
    } else if(document.getElementById('neuralnetwork').checked && document.getElementById('yesCheck').checked) 
	{document.getElementById('statlist').style.display = 'block';
	document.getElementById('dataanalysis').style.display = 'block';
    } else if(document.getElementById('lregression').checked && document.getElementById('yesCheck').checked) 
	{document.getElementById('statlist').style.display = 'none';
	document.getElementById('dataanalysis').style.display = 'none';
    } else {}
}
function updateTextInput(val) {
          //document.getElementById('textInput').value=val;
          numGames = parseInt(document.getElementById('textInputHidden').value)*parseInt(val)/100;
	  document.getElementById('textInput').innerHTML = Math.round(numGames) + " Games"; 
}
</script>

<body onload="yesnoCheck()">
<center>
<h2>NBA Game Score Predictor</h2>
</center>
<div class='main'>
  <form action="nbamlresults.php" method="get">
  <div>
  <p>
  <b>Create New Model:</b> 	
  <input type="radio" onclick="javascript:yesnoCheck();" name="CreateModel" value="Yes" id="yesCheck"> Yes
  <input type="radio" onclick="javascript:yesnoCheck();" name="CreateModel" value="No" id="noCheck" checked> No
  <br>
  <b>Model Type:</b> 	
  <input type="radio" onclick="javascript:typeCheck();"  name="ModelType" value="LinRegression" id="lregression" checked> Linear Regression
  <input type="radio" onclick="javascript:typeCheck();" name="ModelType" value="NeuralNetwork" id="neuralnetwork"> Neural Network
  </p>
	  <div id="dataanalysis" style="display:none"> 
	  <b>Data Analysis:</b>
	  <br>
	  <input type="checkbox" id="normalize" name="Normalize" value="normalize">Normalize Stat Data
	  <br>
	  <input type="checkbox" id="correlationcheck" name="CorrelationCheck" value="normalize">Run Stat Correlation Check
	  </div>
  </div>

<?php
$servername = "localhost";
$username = "nba_stats_user";
$password = "nba_stats_user";
$dbname = "nba_stats";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";

$query_numgames = "SELECT COUNT(game_id) AS NumberOfGames FROM team_stats_per_game";
$query_gameids = "SELECT DISTINCT game_id FROM team_stats_per_game LIMIT ";

$numgames = $conn->query($query_numgames);
	while($numgame = $numgames->fetch_assoc()) {
		//print("Number of Games - ".$numgame['NumberOfGames']);
		$ng = $numgame['NumberOfGames'];
	}

$rdir = "/var/www/html/models/regression";
$nndir = "/var/www/html/models/neuralnetworks";
$rfiles = scandir($rdir);
$nnfiles = scandir($nndir);

//print_r($files);
print("<div id='rmodellist' style=\"padding-left: 0px; display:none\">");
print("<select name='WinnerModel'>\n");
print("<option value=\"nofileselected\">Select a Winning Score Model</option>");
foreach($rfiles as $rfile){
	//print($file);
	if(is_numeric(strpos($rfile,"win"))){print("<option value=".$rfile.">".$rfile."</option>");}
}
print("</select>\n");
print("<select name='LoserModel'>\n");
print("<option value=\"nofileselected\">Select a Losing Score Model</option>");
foreach($rfiles as $rfile){
	if(is_numeric(strpos($rfile,"los"))){print("<option value=".$rfile.">".$rfile."</option>");}
}
print("</select>\n");
print("</div>");

print("<div id='nnmodellist' style=\"padding-left: 0px; display:none\">");
print("<select name='NeuralNetworkModel'>\n");
print("<option value=\"nofileselected\">Select a Model</option>");
foreach($nnfiles as $nnfile){
	//print($file);
	print("<option value=".$nnfile.">".$nnfile."</option>");
}
print("</select>\n");
print("</div>");

$statFields = array('FG_PCT', 'FG3_PCT', 'FT_PCT', 'OREB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TOV', 'PF', 'OFF_RATING', 'DEF_RATING', 'NET_RATING', 'AST_PCT', 'AST_TO', 'AST_RATIO', 'OREB_PCT', 'DREB_PCT', 'REB_PCT', 'TM_TOV_PCT', 'EFG_PCT', 'TS_PCT', 'USG_PCT', 'PACE', 'PIE', 'PTS');

print("<div id='statlist' style=\"padding-left: 0px; display:none\">");
print("<b>Choose Stats:</b>");
print("<p class=\"sub\">Default stats: FG_PCT, AST_PCT</p>");
print("<select name='NeuralNetworkStats[]' multiple size='6'>\n");
foreach($statFields as $stat){
	//print($file);
	print("<option value=".$stat.">".$stat."</option>");
}
print("</select>\n");
print("<b>Choose Number of Training Games:</b>");
print("<div class=\"slidecontainer\">");
print("<input name=\"PercentGames\" type=\"range\" min=\"1\" max=\"100\" value=\"50\" onchange=\"updateTextInput(this.value);\" style=\"width:95%;\">");
print("<center><label id=\"textInput\">".intdiv($ng,2)." Games</label></center>");
print("<input hidden id=\"textInputHidden\" value=\"".$ng."\">");
print("</div>");
print("</div>");

?>
<div>
  <b>Select Teams:</b>
	<select name="Team1">
	<option value="None" selected="selected">Select Team 1</option>
<?php
$teams_query = "SELECT * FROM teams ORDER BY team_name";
$teams = $conn->query($teams_query);
while($team = $teams->fetch_assoc()) {
	print("<option value=\"".$team['team_id']."\" >".$team['team_name']."</option>\n");
	$selected="";
}

print("	</select>");
print("	<select name='Team2'>\n");
print("<option value=\"None\" selected=\"selected\">Select Team 2</option>\n");
$teams->data_seek(0);
while($team = $teams->fetch_assoc()) {
	print("<option value=\"".$team['team_id']."\">".$team['team_name']."</option>\n");
	$selected="";
}

print("	    </select>\n");
print("	    <input name=\"submit\" type=\"submit\" value=\"Submit\">\n");
print("	  </form>\n");
print("	</div>\n");
?>

</body>
</html>

