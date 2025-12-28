 <?php
// USAGE: php parser.php [playlistfile.csv]

// CSV file format:
/*  [0] => Episode No.
    [1] => Date Played
    [2] => Show Title
    [3] => Artist*
    [4] => Title*
    [5] => Year
    [6] => Suggested By 1
    [7] => Suggested By 2
    [8] => Suggested By 3
    [9] => Notes
    [10] => Archive Link
*/
$csv_columns = 11;

// mysql functions
include 'pldb_mysql.php';

// MySQL credentials for testing only
$servername = "127.0.0.1";
$username = "msandbox";
$password = "msandbox";
$port = 8034;
$dbname = "pldb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// open playlist file
if (!isset($argv[1])) {
  die("No file given.");
}
$file = fopen($argv[1], "r");

// iterate through csv file lines
while ($arr = fgetcsv($file, escape: "")) {

  // get array from line and check column count
  
  if (count($arr) != $csv_columns) {
    print("CSV line has wrong number of columns " . count($arr));
    print_r($arr);
    continue;
  }
  // if the first field is empty, assume empty row and skip; also skip on header
  if ($arr[0] === "" || $arr[0] === "Episode No.") {
    continue;
  }
  
  // check show exists; assume show id in file is correct
  $show_id = get_show_id($conn, trim($arr[2]));
  if (!isset($show_id)){
    // insert new show
    $show_id = $arr[0];
    $insert_count = insert_new_show($conn, $arr[0], trim($arr[2]), $arr[1], trim($arr[10]));
  }

  // check artist exists
  $artist_id = get_artist_id($conn, trim($arr[3]));
  if (!isset($artist_id)){
    $artist_id = insert_new_artist($conn, trim($arr[3]));
  }
  
  // check track exists
  $track_id = get_track_id($conn, $artist_id, trim($arr[4]));
  if (!isset($track_id)){
    $track_id = insert_new_track($conn, $artist_id, trim($arr[4]), $arr[5]);
  }
  
  // check play exists (track->show)
  $play_id = get_play_id($conn, $show_id, $track_id);
  if (!isset($play_id)){
    $suggesters = trim($arr[6]);
    if(!empty($arr[7])){
      $suggesters .= "," . trim($arr[7]);
    }
    if(!empty($arr[8])){
      $suggesters .= "," . trim($arr[8]);
    }
    $play_id = insert_new_play($conn, $show_id, $track_id, $suggesters, trim($arr[9]));
  }
}

// close csv file and exit
fclose($file);
exit;
?>
