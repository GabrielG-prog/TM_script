<?php

$zip = new ZipArchive;

if ($zip->open('./EtimocWebXML.zip') === TRUE) {
    $zip->extractTo('./ticketmaster_ftp/dezip_2022/');
    $zip->close();
}

$pdo = new PDO("mysql:host=localhost;dbname=db_etimoc", "root", "root");

$xmlFiles = glob('./ticketmaster_ftp/dezip_2022/*.xml');