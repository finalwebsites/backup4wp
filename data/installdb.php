<?php
try {
    //open the database
    $db = new PDO('sqlite:wpbackupsDb_PDO.sqlite');

    //create the database table
    $db->exec("CREATE TABLE wpbackups ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'dirname' TEXT, 'dirsize' INTEGER, 'insertdate' INTEGER, 'excludedata' TEXT, 'backuptype' TEXT, 'database' INTEGER, 'description' TEXT)");
    // close the database connection
    $db = NULL;
}
catch(PDOException $e) {
    print 'Exception : '.$e->getMessage();
}
