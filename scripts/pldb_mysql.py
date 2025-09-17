# MySQL database modules for pldb

## SHOWS

# get list of all shows by theme, id
# returns dictionary of theme->id
# lowercase for key search
def get_all_shows_by_theme (mycursor):
    query = "SELECT lower(theme), id FROM shows"
    mycursor.execute(query)
    return dict(mycursor.fetchall())

# get list of all shows by id, theme
def get_all_shows_by_id (mycursor):
    query = "SELECT id, theme FROM shows"
    mycursor.execute(query)
    return dict(mycursor.fetchall())


## ARTISTS

# get list of all artists by name, id
# returns dictionary of name->id
# lowercase for key search
def get_all_artists_by_name (mycursor):
    query = "SELECT lower(name), id FROM artists"
    mycursor.execute(query)
    return dict(mycursor.fetchall())


# get artist id from name
# case-insensitive based on column settings
def get_artist_id_by_name (mycursor, name):
    query = "SELECT id FROM artists WHERE name = %s LIMIT 1"
    mycursor.execute(query, (name,))
    result = None
    for row in mycursor.fetchall():
        result = int(row[0])
    return result

## TRACKS

# get track id from artist_id and title
def get_track_id_by_artist_id_and_title(mycursor, artist_id, title):
    query = "SELECT id FROM tracks WHERE artist_id = %s AND title = %s LIMIT 1"
    mycursor.execute(query, (artist_id, title))
    result = None
    for row in mycursor.fetchall():
        result = int(row[0])
    return result

## PLAYS

# get play id from show_id and track_id
def get_play_id_from_show_id_and_track_id(mycursor, show_id, track_id):
    query = "SELECT id FROM plays WHERE show_id = %s AND track_id = %s LIMIT 1"
    mycursor.execute(query, (show_id, track_id))
    result = None
    for row in mycursor.fetchall():
        result = int(row[0])
    return result

# insert new play row if it doesn't exist 
def insert_play(mycursor, show_id, track_id, suggesters, comment):
    result = get_play_id_from_show_id_and_track_id(mycursor, show_id, track_id)
    if result is None:
        query = "INSERT INTO plays (show_id, track_id, suggesters, comment) VALUES (%s, %s, %s, %s)"
        mycursor.execute(query, (show_id, track_id, suggesters, comment))
        result = mycursor.lastrowid
    return result

## SUGGESTERS

def get_suggester_id_by_handle(mycursor, handle):
    query = "SELECT id FROM suggesters WHERE handle = %s LIMIT 1"
    mycursor.execute(query, (handle,))
    result = None
    for row in mycursor.fetchall():
        result = int(row[0])
    return result

# try/catch and trx commit/rollback to be handled by invoker
# checks if the handle is in the database (overkill?)
def insert_suggester(mycursor, handle):
    result = get_suggester_id_by_handle(mycursor, handle)
    if result is None:
        query = "INSERT INTO suggesters (handle) VALUES (%s)"
        mycursor.execute(query, (handle,))
        print("tracks last row id: " + str(mycursor.lastrowid))
        result = mycursor.lastrowid
    return result
