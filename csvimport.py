#!/usr/bin/python3

# Quick and dirty csv importer just to get us off the ground
# SQL var safety and error handling is for wimps. Bodges galore, don't @ me!

import sys
import csv
import mysql.connector

show = sys.argv[1]
airdate = sys.argv[2]
csvfile = sys.argv[3]


mydb = mysql.connector.connect(
  host="localhost",
  user="pldb",
  password="changeme",
  database="pldb",
  ssl_disabled=True
)


def sqlSelectOne(sql):
    """ return one enttry from select style query """
    mycursor = mydb.cursor(buffered=True)
    mycursor.execute(sql)
    return mycursor.fetchone()


def sqlInsert(sql):
    """ add a new entry """
    # Show what we're doing
    print(sql)
    mycursor = mydb.cursor(buffered=True)
    mycursor.execute(sql)
    mydb.commit()


if sqlSelectOne(f"SELECT id FROM shows where id = {show}"):
    theme = sqlSelectOne(f"SELECT theme FROM shows where id = {show}")
    print(f"Show exists with theme {theme[0]}")
else:
    theme = input("What was the theme? ")
    sqlInsert(f"INSERT INTO shows (id, theme, airdate) VALUES (\"{show}\", \"{theme}\", \"{airdate}\")")


# read the csv into memory
with open(csvfile, "r") as f:
    reader = csv.DictReader(f, fieldnames=['artist', 'title', 'year', 'suggester', 'comment'])
    for row in reader:


        # Add artist to db if it doesn't exist
        artistId = sqlSelectOne(f"SELECT id FROM artists WHERE LOWER(name) = LOWER(\"{row["artist"]}\")")
        if artistId is None:
            sqlInsert(f"INSERT INTO artists (name) VALUES (\"{row["artist"].strip()}\")")
            artistId = sqlSelectOne(f"SELECT id FROM artists WHERE LOWER(name) = LOWER(\"{row["artist"]}\")")


        # Add track to db if it doesn't exist
        trackId = sqlSelectOne(f"SELECT id FROM tracks WHERE LOWER(title) = LOWER(\"{row["title"].strip()}\") AND artist_id = {artistId[0]}") 
        if trackId is None:
            year = "Null"
            try:
                year = int(row["year"].strip())
            except:
                print("No year found, continuing")

            sqlInsert(f"INSERT INTO tracks (title, artist_id, year) VALUES (\"{row["title"].strip()}\", {artistId[0]}, {year})")
            trackId = sqlSelectOne(f"SELECT id FROM tracks WHERE LOWER(title) = LOWER(\"{row["title"].strip()}\") AND artist_id = {artistId[0]}") 


        # Add suggester to db if they don't exist
        suggesterId = sqlSelectOne(f"SELECT id FROM suggesters WHERE LOWER(handle) like LOWER(\"{row["suggester"].strip()}%\")")
        if suggesterId is None:
            sqlInsert(f"INSERT INTO suggesters (handle) VALUES (\"{row["suggester"].strip()}\")")
            suggesterId = sqlSelectOne(f"SELECT id FROM suggesters WHERE LOWER(handle) like LOWER(\"{row["suggester"].strip()}%\")")


        # Only quote comment if it exists
        comment = "Null"
        try:
            comment = str(row['comment'].strip())
        except:
            print("No comment found, continuing")
        if comment != "Null":
            comment = f'\"{comment}\"'


        # Add play to db if it doesn't exist
        if sqlSelectOne(f"SELECT id FROM plays WHERE show_id = {show} AND track_id = {trackId[0]} AND suggester_id = {suggesterId[0]}"):
            print("Play already exists")
        else:
            sqlInsert(f"INSERT INTO plays (show_id, track_id, suggester_id, comment) VALUES ({show}, {trackId[0]}, {suggesterId[0]}, {comment})")

