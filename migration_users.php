#!/usr/bin/php

<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

const HOST = 'localhost';
const DB_OLD = 'anaitgames_old_wrong';
const DB_NEW = 'anaitgames';
const USER = 'root';
const PASS = 'root';

const JPG_MIME = 'image/jpeg';
const PNG_MIME = 'image/png';
const GIF_MIME = 'image/gif';
const JPG_EXTENSION = '.jpg';
const PNG_EXTENSION = '.png';
const GIF_EXTENSION = '.gif';

const OLD_NO_PATREON = 0;
const OLD_PATREON_BRONCE = 16;
const OLD_PATREON_PLATA = 17;
const OLD_PATREON_ORO = 18;
const NEW_NO_PATREON = 0;
const NEW_PATREON_BRONCE = 1;
const NEW_PATREON_PLATA = 2;
const NEW_PATREON_ORO = 3;

const SQL_GET_ALL_USERS = 'SELECT * FROM usuario LIMIT 50';
const SQL_GET_LOGROS_FORM_USER = 'SELECT * FROM usuario_logros WHERE usuario_id = :usuario_id AND logro_id in (:patreon_bronce, :patreon_plata, :patreon_oro) ORDER BY logro_id DESC';
const SQL_INSERT_FIXED_USER = <<<EOD
INSERT INTO users(username, full_name, password, email, register_date, role, banned, patreon_level, avatar, rank, twitter_user, register_token, reset_password_token)
VALUES(:username, :full_name, :password, :email, :register_date, :role, :banned, :patreon_level, :avatar, :rank, :twitter_user, :register_token, null)
EOD;

function usuarioGetAvatar($usuario_url, $old_avatar) {
	switch($old_avatar) {
		case JPG_MIME:
			return $usuario_url.JPG_EXTENSION;
		break;
		case PNG_MIME:
			return $usuario_url.PNG_EXTENSION;
		break;
		case GIF_MIME:
			return $usuario_url.GIF_EXTENSION;
		break;
		default:
			return null;
		break;
	}
}

function usuarioGetRole($email_confirmado, $old_role) {
	if($email_confirmado == 0)
		return 0;
	
	switch($old_role) {
		case 2: 
		case 3:
		case 4:
		case 5:
			return 1;			
		break;
		case 8:
			return 2;
		break;
		case 6:
			return 3;
		break;
		case 7:
			return 4;
		break;
	}
}

function usuarioGetPatreonLevel($is_patreon, $logros_usuario) {
	if($is_patreon == OLD_NO_PATREON)
		return NEW_NO_PATREON;

	switch($logros_usuario[0]->logro_id) {
		case OLD_PATREON_BRONCE:
			return NEW_PATREON_BRONCE;
		break;
		case OLD_PATREON_PLATA:
			return NEW_PATREON_PLATA;
		break;
		case OLD_PATREON_ORO:
			return NEW_PATREON_ORO;
		break;
	}
}

$db_old = new PDO('mysql:host='.HOST.';dbname='.DB_OLD.';charset=UTF8', USER, PASS);
$db_old->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$db_old->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
$db_new = new PDO('mysql:host='.HOST.';dbname='.DB_NEW.';charset=UTF8', USER, PASS);
$db_new->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ); 
$db_new->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

print "-----STARTING USERS MIGRATION -----" . PHP_EOL;

foreach($db_old->query(SQL_GET_ALL_USERS) as $usuario) {
	printf("STARTING PROCESS FOR %s %s (%s)\n", $usuario->id, $usuario->usuario, $usuario->usuario_url);
	
	$select_logros = $db_old->prepare(SQL_GET_LOGROS_FORM_USER);
	$select_logros->execute([
		':usuario_id' => $usuario->id,
		':patreon_bronce' => PATREON_BRONCE,
		':patreon_plata' => PATREON_PLATA,
		':patreon_oro' => PATREON_ORO,
	]);
	
	$logros_usuario = $select_logros->fetchAll();
	
	$insert_fixed_user = $db_new->prepare(SQL_INSERT_FIXED_USER);
	$insert_fixed_user->execute([
		':username' => $usuario->usuario_url,
		':full_name' => $usuario->usuario,
		':password' => $usuario->password,
		':email' => $usuario->email,
		':register_date' => $usuario->fecha_alta,
		':role' => usuarioGetRole($usuario->email_confirmado, $usuario->id_rol),
		':banned' => $usuario->id_rol == 1 ? 1 : 0,
		':patreon_level' => usuarioGetPatreonLevel($usuario->patreon, $logros_usuario),
		':avatar' => usuarioGetAvatar($usuario->usuario_url, $usuario->avatar_mime_type),
		':rank' => $usuario->rango,
		':twitter_user' => $usuario->url_twitter,
		':register_token' => $usuario->codconfirm_email
	]);
}

print "-----USERS MIGRATION ENDED-----" . PHP_EOL;