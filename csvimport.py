#!/usr/bin/python3

# quick and dirty csv importer just to get us off the ground
# sql var safety and error handling is for wimps

import sys
import csv
import mysql.connector


# check cli args
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

def matchEntry(table, matcher, entry):
    """ check if an entry exists """

    mycursor = mydb.cursor()
    mycursor.execute(f"SELECT id FROM {table} WHERE {matcher} = '{entry}'")
    return mycursor.fetchone()

def addEntry(table, values):
    """ add a new entry """
    print(f"INSERT INTO {table} ({', '.join(values.keys())}) VALUES ({', '.join(values.values())})")
    mycursor = mydb.cursor()
    mycursor.execute(f"INSERT INTO {table} ({', '.join(values.keys())}) VALUES (\"{'\", \"'.join(values.values())}\")")
    mydb.commit()

#print("shows", "id", show)
#if matchEntry("shows", "id", show):
#    print("Show exists, quitting")
#    sys.exit(1)
#else:
#    theme = input("What was the theme? ")
#    addEntry("shows", {"id": show, "theme": theme, "airdate": airdate})



# read the csv into memory
with open(csvfile, "r") as f:
    reader = csv.DictReader(f, fieldnames=['artist', 'title', 'year', 'suggester', 'comment'])
    for row in reader:

        artistId = matchEntry("artists", "name", row["artist"])
        print(artistId)
        if artistId is None:
          addEntry("artists", {"name": row["artist"].strip()})
          artistId = matchEntry("artists", "name", row["artist"])

        trackId = matchEntry("tracks", "title", row["title"]) # match against the artist too!!!!!
        if trackId is None:
          addEntry("tracks", {"title": row["title"].strip(), "artist_id": str(artistId[0])})
          trackId = matchEntry("tracks", "title", row["title"]) # and here!

  # for each track
    # if it doesn't exist in the tracks table
      # add it to the table (including year)
    # replace track name with id

  # for each suggester
    # if it doesn't exist in the suggesters table
      # add it to the table
    # replace suggester name with id



  # add resulting to plays table