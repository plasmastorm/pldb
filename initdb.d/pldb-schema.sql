CREATE TABLE artists (
    id int NOT NULL,
    name varchar(255),
    link varchar(255),
    PRIMARY KEY (id)
);

CREATE TABLE tracks (
    id int NOT NULL,
    name varchar(255),
    artist_id int,
    length time,
    PRIMARY KEY (id)
);

CREATE TABLE shows (
    id int NOT NULL,
    theme varchar(255),
    airdate datetime,
    PRIMARY KEY (id)
);

CREATE TABLE suggesters (
    id int NOT NULL,
    handle varchar(255),
    PRIMARY KEY (id)
);

CREATE TABLE plays (
    id int NOT NULL,
    show_id int,
    track_id int,
    suggester_id int,
    PRIMARY KEY (id)
);
