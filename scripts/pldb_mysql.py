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
def insert_suggester_by_handle(mycursor, handle):
    result = get_suggester_id_by_handle(mycursor, handle)
    if result is None:
        query = "INSERT INTO suggesters (handle) VALUES (%s)"
        mycursor.execute(query, (handle,))
        print("tracks last row id: " + str(mycursor.lastrowid))
        result = mycursor.lastrowid
    return result
