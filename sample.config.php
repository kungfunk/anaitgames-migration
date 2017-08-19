<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

const HOST = 'localhost';
const DB_OLD = 'database_old';
const DB_NEW = 'database_new';
const USER = 'user';
const PASS = 'pass';

$db_old = new PDO('mysql:host='.HOST.';dbname='.DB_OLD.';charset=UTF8', USER, PASS);
$db_old->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$db_old->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
$db_new = new PDO('mysql:host='.HOST.';dbname='.DB_NEW.';charset=UTF8', USER, PASS);
$db_new->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ); 
$db_new->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);