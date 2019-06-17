<?php
try {
    //open the database
    $db = new PDO('sqlite:data/wpbackupsDb_PDO.sqlite');

    //create the database table
    $db->exec("CREATE TABLE wpbackups ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'dirname' TEXT, 'dirsize' INTEGER, 'insertdate' INTEGER)");
    // close the database connection
    $db = NULL;
}
catch(PDOException $e) {
    print 'Exception : '.$e->getMessage();
}
