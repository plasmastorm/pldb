<?php
if (!defined('ABSPATH')) exit;

function get_all_show_ids($db){
  return $db->get_col("SELECT id from shows ORDER BY id DESC");
}

function get_show_id($db, $theme){
  return $db->get_var($db->prepare("SELECT id FROM shows WHERE theme = %s", $theme));
}

function insert_new_show($db, $id, $theme, $airdate, $archive_link){
  $result = $db->query($db->prepare("INSERT INTO shows (id, theme, airdate, archivelink) VALUES (%d, %s, %s, %s)", $id, $theme, $airdate, $archive_link));
  if ($result === false) throw new Exception('Failed to insert show: '.$db->last_error);
  return $db->rows_affected;
}

function get_artist_id($db, $name){
  // lazy check for bands which may or may not have "The " at the beginning
  // check for both "Band Name" and "The Band Name"
  // works for "The The"
  if (str_starts_with($name, "The ")){
    $name2 = substr($name, 4);
  } else {
    $name2 = "The " . $name;
  }
  $result = $db->get_results($db->prepare("SELECT id FROM artists WHERE name IN (%s, %s) ORDER BY id", $name, $name2));
  
  // simple error message for when we get multiple rows, user will need to investigate themselves
  if (count($result) > 1) {
    error_log("Multiple artist rows for " . $name);
  }
  
  // return the first or only result in the set
  return !empty($result) ? $result[0]->id : null;
}

function insert_new_artist($db, $name){
  $result = $db->query($db->prepare("INSERT INTO artists (name) VALUES (%s)", $name));
  if ($result === false) throw new Exception('Failed to insert artist: '.$db->last_error);
  return $db->insert_id;
}

function get_track_id($db, $artist_id, $title){
  return $db->get_var($db->prepare("SELECT id FROM tracks WHERE artist_id = %d AND title = %s", $artist_id, $title));
}

function insert_new_track($db, $artist_id, $title, $year){
  $result = $db->query($db->prepare("INSERT INTO tracks (artist_id, title, year) VALUES (%d, %s, %d)", $artist_id, $title, $year));
  if ($result === false) throw new Exception('Failed to insert track: '.$db->last_error);
  return $db->insert_id;
}

function get_play_id($db, $show_id, $track_id){
  return $db->get_var($db->prepare("SELECT id FROM plays WHERE show_id = %d AND track_id = %d", $show_id, $track_id));
}

function insert_new_play($db, $show_id, $track_id, $suggesters, $comment){
  $result = $db->query($db->prepare("INSERT INTO plays (show_id, track_id, suggesters, comment) VALUES (%d, %d, %s, %s)", $show_id, $track_id, $suggesters, $comment));
  if ($result === false) throw new Exception('Failed to insert play: '.$db->last_error);
  return $db->insert_id;
}

?>