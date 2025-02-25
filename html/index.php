<?php
$host = 'db';
$user = 'pldb';
$password = 'changeme';
$database = 'pldb';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$show = $_GET['show'] ?? 1;

require(__DIR__.'/templates/header.php');
?>
<div class="col d-flex justify-content-center">
    <div class="card">
        <div class="text-center">
            <h3>Shows</h3>
            <form action="index.php" method="GET">

                <label for="show">Choose a show:</label>

                <select name="show" id="show">
                    <?php
                    $query = "SELECT * FROM shows";
                    $result = $conn->query($query);
                    
                    while ($row = $result->fetch_assoc()) {
                        echo '<option' . ($row['id'] == $show ? " selected ": " ") . 'value="' . $row['id'] . '">#' . $row['id'] . ' - ' . $row['theme'] . ' - ' . $row['airdate'] . '</option>';
                    }
                    ?>
                </select> 
                <input type="submit" value="Go!">
            </form>
            <div>
                <table class="table table-dark">
                    <tr>
                        <th>Artist</th>
                        <th>Track</th>
                        <th>Length</th>
                        <th>Requested By</th>
                    </tr>

                <?php

                    $query = "
                        SELECT artists.name AS artist, artists.link, tracks.name, tracks.length, suggesters.handle
                        FROM plays
                            INNER JOIN tracks
                                ON track_id = tracks.id
                            INNER JOIN suggesters
                                ON suggester_id = suggesters.id
                            INNER JOIN artists
                                ON tracks.artist_id = artists.id 
                        WHERE show_id = '$show'
                        ";
                    $result = $conn->query($query);

                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td><a href="' . $row['link'] . '">' . $row['artist'] . '</a></td>';
                        echo '<td>' . $row['name'] . '</td>';
                        echo '<td>' . $row['length'] . '</td>';
                        echo '<td>' . $row['handle'] . '</td>';
                        echo '</tr>';
                    }
                ?>


                </table>
            </div>
        </div>
    </div>
</div>
<?php
require(__DIR__.'/templates/footer.php');
