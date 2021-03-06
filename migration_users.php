<?php
include 'config.php';

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

const NEW_ROLE_UNCONFIRMED = 0;
const NEW_ROLE_USER = 1;
const NEW_ROLE_MOD = 2;
const NEW_ROLE_EDITOR = 3;
const NEW_ROLE_ADMIN = 4;

const SQL_GET_ALL_USERS = 'SELECT * FROM usuario';
const SQL_GET_LOGROS_FORM_USER = 'SELECT * FROM usuario_logros WHERE usuario_id = :usuario_id AND logro_id in (:patreon_bronce, :patreon_plata, :patreon_oro) ORDER BY logro_id DESC';
const SQL_INSERT_FIXED_USER = <<<EOD
INSERT INTO users(username, full_name, password, email, register_date, role, banned, patreon_level, avatar, rank, twitter_user, register_token, reset_password_token)
VALUES(:username, :full_name, :password, :email, :register_date, :role, :banned, :patreon_level, :avatar, :rank, :twitter_user, :register_token, null)
EOD;
const SQL_INSERT_TEMP_USER_ID = "INSERT INTO _temp_user_id (old_id, new_id) VALUES (:old_id, :new_id)";

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
		return NEW_ROLE_UNCONFIRMED;
	
	switch($old_role) {
        case 1:
		case 2: 
		case 3:
		case 4:
		case 5:
			return NEW_ROLE_USER;
		break;
		case 8:
			return NEW_ROLE_MOD;
		break;
		case 6:
			return NEW_ROLE_EDITOR;
		break;
		case 7:
			return NEW_ROLE_ADMIN;
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

	return NEW_NO_PATREON;
}

print "-----STARTING USERS MIGRATION -----" . PHP_EOL;

foreach($db_old->query(SQL_GET_ALL_USERS) as $usuario) {
	printf("STARTING PROCESS FOR %s %s (%s)\n", $usuario->id, $usuario->usuario, $usuario->usuario_url);
	
	$select_logros = $db_old->prepare(SQL_GET_LOGROS_FORM_USER);
	$select_logros->execute([
		':usuario_id' => $usuario->id,
		':patreon_bronce' => OLD_PATREON_BRONCE,
		':patreon_plata' => OLD_PATREON_PLATA,
		':patreon_oro' => OLD_PATREON_ORO,
	]);
	
	$logros_usuario = $select_logros->fetchAll();
	
	$insert_fixed_user = $db_new->prepare(SQL_INSERT_FIXED_USER);
	$insert_fixed_user->execute([
		':username' => $usuario->usuario_url,
		':full_name' => $usuario->usuario,
		':password' => password_hash($usuario->password, PASSWORD_DEFAULT), //Added bcryt to already hashed passwords
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

    $user_new_id = $db_new->lastInsertId();

    $insert_temp_user_id = $db_new->prepare(SQL_INSERT_TEMP_USER_ID);
    $insert_temp_user_id->execute([
        ':old_id' => $usuario->id,
        ':new_id' => $user_new_id
    ]);
}

print "-----USERS MIGRATION ENDED-----" . PHP_EOL;
