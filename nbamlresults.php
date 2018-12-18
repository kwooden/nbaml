<!DOCTYPE html>
<html>
<head>
  <title>NBA ML Results</title>
</head>
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

div {
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
}

div.main {
    border-radius: 5px;
    margin: auto;
    width:650px;
    background-color: #f2f2f2;
    padding: 20px;
}

</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<body>

<h2>NBA Game Score Predictor - Results</h2>

<div class='main'>
<?php
$servername = "localhost";
$username = "nba_stats_user";
$password = "nba_stats_user";
$dbname = "nba_stats";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    exit;
}
//echo "Connected successfully";

?>

<?php 
require_once __DIR__ . '/vendor/autoload.php';
use Phpml\Classification\MLPClassifier;
use Phpml\NeuralNetwork\ActivationFunction\PReLU;
use Phpml\NeuralNetwork\ActivationFunction\Sigmoid;
use Phpml\NeuralNetwork\Layer;
use Phpml\NeuralNetwork\Node\Neuron;
use Phpml\Regression\LeastSquares;
use Phpml\ModelManager;
use Phpml\Preprocessing\Normalizer;

if(isset($_GET['Team1']) && isset($_GET['Team2'])){
//****** Python script request to NBA Stats API *********************************
$output = shell_exec("python nbastatsoneteam.py ".$_GET['Team1']." ".$_GET['Team2']);
} else {exit("Team 1 or 2 not Choosen");exit;}

//print($_GET['Normalize']);
$query_team1names = "SELECT team_name FROM teams WHERE team_id = ".$_GET['Team1'];
$query_team2names = "SELECT team_name FROM teams WHERE team_id = ".$_GET['Team2'];
$team1name = $conn->query($query_team1names);
$team2name = $conn->query($query_team2names);
if($team1name->num_rows > 0) {
	while($name = $team1name->fetch_assoc()){
	$Team1 = $name["team_name"];}
} else{print("Didn't Find Team 1");}
if($team2name->num_rows > 0) {
	while($name = $team2name->fetch_assoc()){
	$Team2 = $name["team_name"];}
} else{print("Didn't Find Team 2");}

if(!empty($output)) {
	//print("Python script Successful <br>");
	//print($output."<br>");
	$output = str_replace("u'","",$output);
	$output = str_replace("'","",$output);
	//print($output."<br>");
} else{print("Python script Failed <br>");exit;}

// Linear Regression Algorithm *******************************************************************************
if($_GET['ModelType'] == "LinRegression"){
	print("<h3>Algorithm: Linear Regression</h3>");
	print("<h4>Team 1: ".$Team1."<br>Team 2: ".$Team2."</h4>");
	$searchValues[] = "EFG_PCT";
	$searchValues[] = "haEFG_PCT"; //H Team advanced stat
	$searchValues[] = "aaEFG_PCT"; //A Team advanced stat
	preg_match_all('/ '.$searchValues[1].': ([^\s]+)[,]/', $output, $lrResults1);
	preg_match_all('/ '.$searchValues[2].': ([^\s]+)[,]/', $output, $lrResults2);
	//print_r($lrResults1);
	//print_r($lrResults2);
	$num_games = intval($_GET['PercentGames'])/100;
	$query_numgames = "SELECT COUNT(game_id) AS NumberOfGames FROM team_stats_per_game";
	$query_gameids = "SELECT DISTINCT game_id FROM team_stats_per_game LIMIT ";

	$numgames = $conn->query($query_numgames);
		while($numgame = $numgames->fetch_assoc()) {
			//print("Number of Games - ".$numgame['NumberOfGames']);
			$ng = $numgame['NumberOfGames'];
		}
	$query_gameids .= intval($ng * $num_games);
	//print($query_gameids);
	$gameids = $conn->query($query_gameids);
	if ($gameids->num_rows > 0) {
		//print("Numbers of Rows - ".$gameids->num_rows."<br>");
	    	while($gameid = $gameids->fetch_assoc()) {
			//print("Game ID - ".$gameid["game_id"]."<br>");
			$query_teamstats = "SELECT * FROM team_stats_per_game WHERE game_id = ".$gameid["game_id"]." ORDER BY PTS DESC";
			//print($query_teamstats);
			$win_los_teamstats = $conn->query($query_teamstats);
			$x=0;
			while($teamstats = $win_los_teamstats->fetch_assoc()) {
				//print("<br>Team Name - ".$teamstats["team_name"]." ");
				$y=0;
				foreach($teamstats as $key => $value){
					if(in_array($key, [$searchValues[0]])) {			
					$tempstats[] = $value;}
					$y++;
				}
				if($x == 0){$winningteampts[] = $teamstats['PTS'];}
				if($x == 1){$losingteampts[] = $teamstats['PTS'];}
				$x = 1;
			}
			//print("<br>");
			$mlteamstats[] = $tempstats;
			unset($tempstats);
	    	}
		$win_los_teamstats->close();
		$gameids->close();
		$i=0;
		/*
		foreach($mlteamstats as $values){
			print("[");
			foreach($values as $value){  		
			print($value.", ");
			}
			print("]<br>");
		}
	
		print("<br> Winning Scores <br>");
		print_r($winningteampts);
		print("<br> Losing Scores <br>");
		print_r($losingteampts);
		*/
	}

	$filepath = '/var/www/html/models/regression/';
	$wmodelManager = new ModelManager();
	if (file_exists($filepath) && $_GET['CreateModel'] == 'No') {
		$filename = $filepath.$_GET['WinnerModel'];
		$winScoreModel = $wmodelManager->restoreFromFile($filename);
	} elseif(file_exists($filepath)) {
		$filename = $filepath."winnerModel".date("Ymdhis").".ml";	
		$winnerRegression = new LeastSquares();
		$winnerRegression->train($mlteamstats, $winningteampts);
		$wmodelManager->saveToFile($winnerRegression, $filename);
		$winScoreModel = $wmodelManager->restoreFromFile($filename);	
	}

	//$winningTeam = [1, 21701214, 1610612755, 0.494, 0.459, 0.600, 11, 43, 54, 29, 7, 8, 18, 22, 112.9, 101.4, 11.5, 0.674, 1.61, 19.7, 0.262, 0.843, 0.581, 16.791, 0.592, 0.604, 0.200, 109.32, 0.565 ];
	//$winningTeam = [0.592, 0.485];
	$winningTeam = [$lrResults1[1][0], $lrResults2[1][0]];
	print("<h4> Winning Score Prediction: ".$winScoreModel->predict($winningTeam)."<h4>");

	$lmodelManager = new ModelManager();
	if (file_exists($filepath) && $_GET['CreateModel'] == 'No') {
		$filename = $filepath.$_GET['LoserModel'];
		$losScoreModel = $lmodelManager->restoreFromFile($filename);
	} elseif(file_exists($filepath)) {
		$filename = $filepath."loserModel".date("Ymdhis").".ml";
		$loserRegression = new LeastSquares();
		$loserRegression->train($mlteamstats, $losingteampts);
		$lmodelManager->saveToFile($loserRegression, $filename);
		$losScoreModel = $lmodelManager->restoreFromFile($filename);	
	}

	//$losingTeam = [1, 21701214, 1610612755, 0.494, 0.459, 0.600, 11, 43, 54, 29, 7, 8, 18, 22, 112.9, 101.4, 11.5, 0.674, 1.61, 19.7, 0.262, 0.843, 0.581, 16.791, 0.592, 0.604, 0.200, 109.32, 0.565 ];
	//$losingTeam = [0.592, 0.485];
	$losingTeam = [$lrResults1[1][0], $lrResults2[1][0]];
	print("<h4> Losing Score Prediction: ".$losScoreModel->predict($losingTeam)."<h4>");

// Neural Network Algorithm *******************************************************************************
} elseif ($_GET['ModelType'] == "NeuralNetwork") {
	print("<h3>Algorithm: Neural Network</h3>");
	print("<h4>Team 1: ".$Team1."<br>Team 2: ".$Team2."</h4>");
	//print($_GET['PercentGames']);
	$num_games = intval($_GET['PercentGames'])/100;
	$query_numgames = "SELECT COUNT(game_id) AS NumberOfGames FROM team_stats_per_game";
	$query_gameids = "SELECT DISTINCT game_id FROM team_stats_per_game LIMIT ";
	//print_r($_GET['NeuralNetworkStats']);
	
	if(isset($_GET['NeuralNetworkStats'])){$num_stats = count($_GET['NeuralNetworkStats']);}
	//print($query_gameids);
	$statFields = array('FG_PCT', 'FG3_PCT', 'FT_PCT', 'OREB', 'DREB', 'REB', 'AST', 'STL', 'BLK', 'TOV', 'PF', 'OFF_RATING', 'DEF_RATING', 'NET_RATING', 'AST_PCT', 'AST_TOV', 'AST_RATIO', 'OREB_PCT', 'DREB_PCT', 'REB_PCT', 'TM_TOV_PCT', 'EFG_PCT', 'TS_PCT', 'USG_PCT', 'PACE', 'PIE', 'PTS');
	//print_r($statFields);
	$query_stats="";
	$model_stats = array('FG_PCT','AST_PCT');
	if(isset($_GET['NeuralNetworkStats']) && $_GET['CreateModel'] == 'Yes') {
		foreach($_GET['NeuralNetworkStats'] as $stats){
			$query_stats .= $stats.", ";
			$eachStat[] = $stats;
		}
		//print($query_stats);
	} else if($_GET['CreateModel'] == 'Yes') {	
		foreach($model_stats as $stats){
			$query_stats .= $stats.", ";
		}
		$num_stats = count($model_stats);
		print("<p>Default stats: FG_PCT, AST_PCT</p>");
	} else {
		foreach($model_stats as $stats){
			$query_stats .= $stats.", ";
		}
	}	
	$query_stats = rtrim($query_stats, ", ");

	$numgames = $conn->query($query_numgames);
		while($numgame = $numgames->fetch_assoc()) {
			//print("Number of Games - ".$numgame['NumberOfGames']);
			$ng = $numgame['NumberOfGames'];
		}
	$query_gameids .= intval($ng * $num_games);
	//print($query_gameids);
	$gameids = $conn->query($query_gameids);
	if ($gameids->num_rows > 0) {
		//print("Numbers of Rows - ".$gameids->num_rows."<br>");
	    	while($gameid = $gameids->fetch_assoc()) {
			//print("Game ID - ".$gameid["game_id"]."<br>");
			$query_teamstats = "SELECT ".$query_stats." FROM team_stats_per_game WHERE game_id = ".$gameid["game_id"]." ORDER BY PTS DESC";
			//print($query_teamstats);
			$win_los_teamstats = $conn->query($query_teamstats);
			$x=0;
			while($teamstats = $win_los_teamstats->fetch_assoc()) {
				//print("<br>Team Name - ".$teamstats["team_name"]." ");
				foreach($teamstats as $key => $value){		
					if($key != 'team_name') {
						if($x==0){$tkey = "w".$key;}elseif($x==1){
						$tkey = "l".$key;}
						$tempstats[$tkey] = floatval($value);
					}
				}
				$x=1;$tkey="";
			}
			$winnerorloserstats[] = 1;
			$mlteamstats[] = $tempstats;
			unset($tempstats);
			$query_teamstats = "SELECT ".$query_stats." FROM team_stats_per_game WHERE game_id = ".$gameid["game_id"]." ORDER BY PTS ASC";
			$win_los_teamstats = $conn->query($query_teamstats);
			$x=0;
			while($teamstats = $win_los_teamstats->fetch_assoc()) {
				//print("<br>Team Name - ".$teamstats["team_name"]." ");
				foreach($teamstats as $key => $value){		
					if($key != 'team_name') {
						if($x==0){$tkey = "l".$key;}elseif($x==1){
						$tkey = "w".$key;}
						$tempstats[$tkey] = floatval($value);
					}

				}
				$x=1;$tkey="";
			}
			$winnerorloserstats[] = -1;
			$mlteamstats[] = $tempstats;
			unset($tempstats);
		}
	}
	//print_r($mlteamstats);

	if($_GET['CreateModel'] == 'Yes') {
		print("<b>Stats:</b><br>");
		foreach($_GET['NeuralNetworkStats'] as $stat){
			print($stat."<br>");
		}
	}

	$count=0;
/*	
	foreach($mlteamstats as $key => $stats) {
		if(abs($stats["wFG_PCT"]-$stats["lFG_PCT"]) < 0.07) {
		//print("<br>Remove");
		//print_r($mlteamstats[$key]);
		unset($mlteamstats[$key]);
		unset($winnerorloserstats[$count]);
		}
		$count++;
		
		print("[");
		foreach($stats as $stat) {
		print($stat.",");
		}
		print("]");
		
	}
*/	
	//print_r($winnerorloserstats);
	//$winnerorloserstats = array (1, -1, 1, -1,1,-1,1, -1, 1, -1);
	$nnmodelManager = new ModelManager();
	if(isset($_GET['Normalize'])) {
		$normalizer = new Normalizer();
		$normalizer->transform($mlteamstats);
		//print("<br>Normalized Training Data<br>");
		//Test Data
		//$mlteamstats = array ([0.494,0.674,0.418,0.659],[0.418,0.659,0.494,0.674],[0.481,0.632,0.402,0.514],[0.402,0.514,0.481,0.632],[0.447,0.548,0.367,0.606],[0.367,0.606,0.447,0.548],[0.451,0.537,0.42,0.649],[0.42,0.649,0.451,0.537],[0.482,0.805,0.344,0.576],[0.344,0.576,0.482,0.805],[0.442,0.81,0.356,0.667],[0.356,0.667,0.442,0.81],[0.533,0.688,0.532,0.634],[0.532,0.634,0.533,0.688],[0.489,0.636,0.443,0.462],[0.443,0.462,0.489,0.636],[0.452,0.667,0.44,0.649],[0.44,0.649,0.452,0.667]);
	}
	foreach($mlteamstats as $key => $stats) {
		$mlteamstats[$key] = array_values($mlteamstats[$key]);
	}
	//print_r($mlteamstats);
	$filepath = '/var/www/html/models/neuralnetworks/';
	if (file_exists($filepath) && $_GET['CreateModel'] == 'No') {
	// Retrieve Neural Network Model -----------------------------------------------------
		$filename = $filepath.$_GET['NeuralNetworkModel'];
		$neuralNetwork = $nnmodelManager->restoreFromFile($filename);
		$model_query = "SELECT model_stats FROM ml_models WHERE modelname = '".$filename."'";
		$modelstats = $conn->query($model_query);
		print("<p><b>Model Used:</b><br> ".str_replace($filepath,"",$filename)."</p>");
		print("<b>Stats:</b>");
		$model_stats = array();
		while($teamstats = $modelstats->fetch_assoc()) {
			print("<br>".$teamstats['model_stats']);
			$model_stats[] = $teamstats['model_stats'];
		}
				
	} elseif (file_exists($filepath)) {
	// Create Neural Network Model -----------------------------------------------------
		$num_hidelayers = intval($num_stats/4);
		$num_neurons = intval($num_stats * 2);
		$filename = $filepath."NeuralNetworkModel".date("Ymdhis")."_N".$num_neurons.".ml";
		//$neuralNetwork = new MLPClassifier(16, [[4, new PReLU], [4, new Sigmoid]], [1,-1]);
		$neuralNetwork = new MLPClassifier($num_neurons, [[$num_hidelayers, new PReLU], [$num_hidelayers, new Sigmoid]], [1,-1]);
		$neuralNetwork->train($mlteamstats, $winnerorloserstats);
		$nnmodelManager->saveToFile($neuralNetwork, $filename);
		$neuralNetwork = $nnmodelManager->restoreFromFile($filename);
		// Load model name and stats in database ------------------------------------
		foreach($_GET['NeuralNetworkStats'] as $stat){
			$model_insert = "INSERT INTO ml_models (modelname, model_stats) VALUES ('".$filename."','".$stat."')";
			if ($conn->query($model_insert) === TRUE) {
			    //echo "New record created successfully";
			} else {
			    echo "Error: " . $sql . "<br>" . $conn->error;
			}
		}
	} else {}
	// Search Python script request results for stats****************************************************************************
	if(isset($_GET['NeuralNetworkStats'])) {
		foreach($_GET['NeuralNetworkStats'] as $stat){
			preg_match_all('/hb'.$stat.': ([^\s]+)[,]/', $output, $lrResults1);
			preg_match_all('/ha'.$stat.': ([^\s]+)[,]/', $output, $lrResults2);
			foreach($lrResults1[1] as $value){
			$teamsStats[] = floatval($value);}	
			foreach($lrResults2[1] as $value){
			$teamsStats[] = floatval($value);}
		}
		foreach($_GET['NeuralNetworkStats'] as $stat){
			preg_match_all('/ab'.$stat.': ([^\s]+)[,]/', $output, $lrResults3);
			preg_match_all('/aa'.$stat.': ([^\s]+)[,]/', $output, $lrResults4);
			foreach($lrResults3[1] as $value){
			$teamsStats[] = floatval($value);}
			foreach($lrResults4[1] as $value){
			$teamsStats[] = floatval($value);}
		}
	} else {
		foreach($model_stats as $stat){
			preg_match_all('/hb'.$stat.': ([^\s]+)[,]/', $output, $lrResults1);
			preg_match_all('/ha'.$stat.': ([^\s]+)[,]/', $output, $lrResults2);
			foreach($lrResults1[1] as $value){
			$teamsStats[] = floatval($value);}	
			foreach($lrResults2[1] as $value){
			$teamsStats[] = floatval($value);}
		}
		foreach($model_stats as $stat){
			preg_match_all('/ab'.$stat.': ([^\s]+)[,]/', $output, $lrResults3);
			preg_match_all('/aa'.$stat.': ([^\s]+)[,]/', $output, $lrResults4);
			foreach($lrResults3[1] as $value){
			$teamsStats[] = floatval($value);}
			foreach($lrResults4[1] as $value){
			$teamsStats[] = floatval($value);}
		}
	}
	print("<br>");
	//print_r($teamsStats);
	$teamsStats2[] = $teamsStats;
	//print_r($teamsStats2);
	if(isset($_GET['Normalize'])) {$normalizer->transform($teamsStats2);}
	//print("<br>Test Set<br>");	
	//$teamsStats2 = array(0.44646980521665, 0.5845991860721, 0.38943573828279, 0.55429983801349);	
	//$teamsStats2 = array (0.494, 0.674, 0.418, 0.659);
	//print_r($teamsStats2);
	$results = $neuralNetwork->predict($teamsStats2);
	//print("<br>".$results[0]."<br>");
	if($results == 1){print("<h4>Winner: ".$Team1."</h4>");}
	else {print("<h4>Winner: ".$Team2."</h4>");}


} else {print("Error - no Model type choosen");}

// ********* CREATE HOME TEAM BIAS **************

print("<p><a href=\"nbaml.php\">Run New Prediction</a></p>");

// ************** Chart and Linear Convergance Check *********************

if(isset($_GET['CorrelationCheck'])) {
print("<hr>");
print("<h3>Stat Correlation Check</h3>");

print("Number of Stats: ".$num_stats."<br>");
$count = 0;
for ($x = 0; $x < $num_stats; $x++) {
	for($y = $x+1; $y < $num_stats; $y++) {	
		if($x!=$y) {
			//print($x." ".$y); 
			//print(" - ".$eachStat[$x]." vs ".$eachStat[$y]."<br>");
			$statTitle = $eachStat[$x]." vs ".$eachStat[$y];
			$queryStats = $eachStat[$x].", ".$eachStat[$y];
			$chart_data = "";
			mysqli_data_seek($gameids, 0);
			$chart_data = "data: [";
			while($gameid = $gameids->fetch_assoc()) {

				$query_teamstats = "SELECT ".$queryStats." FROM team_stats_per_game WHERE game_id = ".$gameid["game_id"]." ORDER BY PTS DESC";
				//print($query_teamstats);
				$win_los_teamstats = $conn->query($query_teamstats);
				while($teamstats = $win_los_teamstats->fetch_array()) {
					$chart_data .= "{x: ".$teamstats[0].",";
					$chart_data .= "y: ".$teamstats[1]."},";
					$stat1[] = $teamstats[0];// Convert first stat to an array
					$stat2[] = $teamstats[1];// Convert second stat to an array
				}
			}
			$correlation = stats_stat_correlation($stat1, $stat2);//Using PHP function to calculate Pearson coefficient
			print("<br>");
			print("<b>Graph of ".$statTitle."</b><br>");
			print("Pearson Correlation Coefficient: ".number_format($correlation,5));
			$chart_data .= "]";
			$chart_data = rtrim($chart_data, ",");
			//print($chart_data);

			//**Following creates Scatter Plot using Chartjs**
			print("<canvas id=\"myChart".$count."\" width=\"600\" height=\"600\"></canvas>");
			print("<script>");
			print("var ctx = document.getElementById(\"myChart".$count."\");");
			print("var myChart = new Chart(ctx, {");
			print("type: 'scatter',");
			print("data: {");
			print("    datasets: [{");
			print("        label: 'Scatter Plot',");
			print("borderColor:  '#3c33ff' ,");
			//print("backgroundColor: '#3c33ff',");
			print($chart_data);
			print("    }]");
			print("},");
			print("options: {");
			print("    responsive: false,");
			print("    scales: {");
			print("        yAxes: [{");
			print("            ticks: {");
			print("                beginAtZero:false");
			print("            }");
			print("        }]");
			print("    }");
			print("}");
			print("});");
			print("</script>");
			$count++;
			$stat1 = array();
			$stat2 = array();
		} 
	}
}

}

// **********************************************************************************

$conn->close();

?>

</body>
</html>

