 <?php

// returns an array of show id numbers -- we're assuming this is always filled out
function get_all_show_ids($conn){
  $qry = "SELECT id from shows ORDER BY id DESC";
  // mysqli_query(connection, query, resultmode)
  $result = mysqli_query($conn, $qry);
  // echo "Returned rows are: " . mysqli_num_rows($result);
  $rows = mysqli_fetch_all($result, MYSQLI_NUM);
  // Free result set
  mysqli_free_result($result);
  return $rows;
}

// returns show_id based on theme text
function get_show_id($conn, $theme){
  /* create a prepared statement */
  $stmt = mysqli_prepare($conn, "SELECT id FROM shows WHERE theme = ?");
  /* bind parameters for markers */
  mysqli_stmt_bind_param($stmt, "s", $theme);
  /* execute query */
  mysqli_stmt_execute($stmt);
  /* bind result variables */
  mysqli_stmt_bind_result($stmt, $id);
  /* fetch value and close */
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  
  return $id;
}

// returns affected_rows
function insert_new_show($conn, $id, $theme, $airdate, $archive_link){
  $stmt = mysqli_prepare($conn, "INSERT INTO shows (id, theme, airdate, archivelink) VALUES (?, ?, ?, ?)");
  mysqli_stmt_bind_param($stmt, "isss", $id, $theme, $airdate, $archive_link);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return mysqli_affected_rows($conn);
}

// returns artist_id
function get_artist_id($conn, $name){
  // lazy check for bands which may or may not have "The " at the beginning
  // check for both "Band Name" and "The Band Name"
  // works for "The The"
  if (str_starts_with(strtolower($name), "the ")){
    $name2 = substr($name, 4);
  } else {
    $name2 = "The " . $name;
  }

  // lazy check for and/&
  // $name3 and $name4 end up being lowercase; doesn't matter in the database query
  // but PHP string functions are case sensitive
  if (str_contains(strtolower($name), " and ")) {
    $name3 = str_ireplace(" and ", " & ", $name);
    $name4 = str_ireplace(" and ", " & ", $name2);
  } elseif (str_contains(strtolower($name), " & ")) {
    $name3 = str_ireplace(" & ", " and ", $name);
    $name4 = str_ireplace(" & ", " and ", $name2);
  } else {
    // if there are no replacements of and/& to name, just repeat $name for the other variables
    // it's redundant but doesn't change the query result
    $name3 = $name;
    $name4 = $name;
  }

  $query = "SELECT id FROM artists WHERE name IN (?, ?, ?, ?) ORDER BY id";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "ssss", $name, $name2, $name3, $name4);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);

  // simple error message for when we get multiple rows, user will need to investigate themselves
  $row_count = mysqli_stmt_num_rows($stmt);
  if ($row_count > 1) {
    print("Multiple artist rows for " . $name . "\n");
  }
  
  // return the first or only result in the set
  mysqli_stmt_bind_result($stmt, $id);

  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  return $id;
}

// returns artist_id
function insert_new_artist($conn, $name){
  $query = "INSERT INTO artists (name) VALUES (?)";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "s", $name);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return mysqli_insert_id($conn);
}

// returns track id
function get_track_id($conn, $artist_id, $title){
  $query = "SELECT id FROM tracks WHERE artist_id = ? AND title = ?";
  $stmt = null;
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "is", $artist_id, $title);
  
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $id);

  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  return $id;
}

// returns track_id
function insert_new_track($conn, $artist_id, $title, $year){
  $query = "INSERT INTO tracks (artist_id, title, year) VALUES (?, ?, ?)";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "isi", $artist_id, $title, $year);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return mysqli_insert_id($conn);
}

// returns play id
function get_play_id($conn, $show_id, $track_id){
  $query = "SELECT id FROM plays WHERE show_id = ? AND track_id = ?";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "ii", $show_id, $track_id);
  mysqli_stmt_execute($stmt);

  /* bind result variables */
  mysqli_stmt_bind_result($stmt, $id);

  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  return $id;
}

// returns track_id
function insert_new_play($conn, $show_id, $track_id, $suggesters, $comment){
  $query = "INSERT INTO plays (show_id, track_id, suggesters, comment) VALUES (?, ?, ?, ?)";
  $stmt = mysqli_prepare($conn, $query);
  mysqli_stmt_bind_param($stmt, "iiss", $show_id, $track_id, $suggesters, $comment);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  return mysqli_insert_id($conn);
}
?>
