#!/usr/bin/env python3

import csv
import mysql.connector
import pldb_mysql

# db connection
mydb = mysql.connector.connect(
  host="127.0.0.1",
  port=8034,
  user="msandbox",
  password="msandbox",
  database="pldb"
)

mycursor = mydb.cursor(buffered=True)

# get list of shows and artists
shows = pldb_mysql.get_all_shows_by_theme(mycursor)
# print(shows)

# DictReader reads the first row as column names
# Episode No.,Date Played,Show Title,Artist,Title,Year,Suggested By 1,Suggested By 2,Suggested By 3,Notes

with open('theplaylist-2025-08-16.csv', newline='') as csvfile:
    trackreader = csv.DictReader(csvfile)
    for row in trackreader:
        # ignore empty rows
        if row['Episode No.'] == "":
            continue

        # get show details
        show_id = int(row['Episode No.'])
        theme = row['Show Title'].strip()

        if theme.lower() not in shows:
            # insert show into database
            showInsert = "INSERT INTO shows (id, theme, airdate) VALUES (%s, %s, %s)"
            try:
                mycursor.execute(showInsert, (show_id, theme, row['Date Played'].strip()))
                mydb.commit()
                shows[theme.lower()] = show_id
            except mysql.connector.IntegrityError as e:
                print("Error: {}".format(e))
                if e.args[0] != 1062:
                    mydb.rollback()
                    continue
        # TODO: add else if show id and theme don't match?

        artist = row['Artist'].strip()
        artist_id = pldb_mysql.get_artist_id_by_name(mycursor, artist)
        if not isinstance(artist_id, int):
            artistInsert = "INSERT INTO artists (name) VALUES (%s)"
            try:
                mycursor.execute(artistInsert, (artist,))
                mydb.commit()
                print("last row id: " + str(mycursor.lastrowid))
                artist_id = mycursor.lastrowid
            except mysql.connector.IntegrityError as e:
                print("Error: {}".format(e))
                if e.args[0] != 1062:
                    mydb.rollback()
                    continue

        # tracks
        track_id = pldb_mysql.get_track_id_by_artist_id_and_title(mycursor, artist_id, row['Title'])
        if not isinstance(track_id, int):
            track_insert = "INSERT INTO tracks (title, artist_id, year) VALUES (%s, %s, %s)"
            year = None
            if isinstance(row['Year'], int):
                year = row['Year']
                print("Year: " + str(year))
            try:
                mycursor.execute(track_insert, (row['Title'], artist_id, year))
                mydb.commit()
                print("tracks last row id: " + str(mycursor.lastrowid))
                track_id = mycursor.lastrowid
            except mysql.connector.IntegrityError as e:
                print("Error: {}".format(e))
                if e.args[0] != 1062:
                    mydb.rollback()
                    continue
        
        # plays
        # get suggesters
        suggesters = " ".join([row['Suggested By 1'], row['Suggested By 2'], row['Suggested By 3']]).strip()
        print(suggesters)
        try:
            pldb_mysql.insert_play(mycursor, show_id, track_id, suggesters)
            mydb.commit()
            print("plays last row id: " + str(mycursor.lastrowid))
        except mysql.connector.IntegrityError as e:
            print("Error: {}".format(e))
            if e.args[0] != 1062:
                mydb.rollback()
                continue
