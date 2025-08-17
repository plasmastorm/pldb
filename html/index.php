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
    <div class="card p-3">
        <div class="text-center">

            <form action="index.php" method="GET" class="p-4">

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
            <h3 class="p-2">Show 
            <?php
                    $query = "SELECT * FROM shows where id = '$show'";
                    $result = $conn->query($query);
                    
                    while ($row = $result->fetch_assoc()) {
                        echo '#' . $row['id'] . ' - ' . $row['theme'] . ' - ' . $row['airdate'];
                    }
                    ?>
            </h3>
            <div>
                <table class="table">
                    <thead class="thead-dark">
                        <tr>
                            <th>Artist</th>
                            <th>Title</th>
                            <th>Year</th>
                            <th>Requested By</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                <?php
                    $query = '
                        SELECT artists.name AS artist, tracks.title, tracks.year, suggesters.handle, comment
                        FROM plays
                            INNER JOIN tracks
                                ON track_id = tracks.id
                            INNER JOIN suggesters
                                ON suggester_id = suggesters.id
                            INNER JOIN artists
                                ON tracks.artist_id = artists.id 
                        WHERE show_id = ' . $show;
                    $result = $conn->query($query);

                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row['artist'] . '</td>';
                        echo '<td>' . $row['title'] . '</td>';
                        echo '<td>' . $row['year'] . '</td>';
                        echo '<td>' . $row['handle'] . '</td>';
                        echo '<td>' . $row['comment'] . '</td>';
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
