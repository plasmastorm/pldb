CREATE TABLE artists (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255),
    PRIMARY KEY (id),
    UNIQUE KEY (name)
);

CREATE TABLE tracks (
    id int NOT NULL AUTO_INCREMENT,
    title varchar(255),
    artist_id int,
    year int,
    PRIMARY KEY (id),
    KEY ind_artist (artist_id)
);

CREATE TABLE shows (
    id int NOT NULL,
    theme varchar(255),
    airdate date,
    published boolean,
    archivelink varchar(2048),
    applemusiclink varchar(2048),
    spotifylink varchar(2048),
    PRIMARY KEY (id),
    KEY ind_theme (theme)
);

CREATE TABLE suggesters (
    id int NOT NULL AUTO_INCREMENT,
    handle varchar(255),
    PRIMARY KEY (id),
    UNIQUE KEY ind_handle (handle)
);

CREATE TABLE plays (
    id int NOT NULL AUTO_INCREMENT,
    show_id int,
    track_id int,
    suggester_id int,
    comment varchar(255),
    PRIMARY KEY (id),
    UNIQUE KEY ind_show_track (show_id, track_id),
    KEY ind_track (track_id),
    KEY ind_suggester (suggester_id)
);
