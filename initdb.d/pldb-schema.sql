CREATE TABLE artists (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255),
    PRIMARY KEY (id)
);

CREATE TABLE tracks (
    id int NOT NULL AUTO_INCREMENT,
    title varchar(255),
    artist_id int,
    year int,
    PRIMARY KEY (id)
);

CREATE TABLE shows (
    id int NOT NULL,
    theme varchar(255),
    airdate date,
    published boolean,
    archivelink varchar(255),
    PRIMARY KEY (id)
);

CREATE TABLE suggesters (
    id int NOT NULL AUTO_INCREMENT,
    handle varchar(255),
    PRIMARY KEY (id)
);

CREATE TABLE plays (
    id int NOT NULL AUTO_INCREMENT,
    show_id int,
    track_id int,
    suggester_id int,
    comment varchar(255),
    PRIMARY KEY (id)
);
