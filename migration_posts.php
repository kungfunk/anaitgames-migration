#!/usr/bin/php

<?php
include config.php

const SQL_SELECT_POSTS = "SELECT * FROM articulo LIMIT 50";
const SQL_SELECT_USER_NEW_ID = "SELECT new_id FROM _temp_user_id WHERE old_id = :old_id";
const SQL_INSERT_FIXED_POST = <<<EOD
INSERT INTO posts (user_id, post_type_id, status, creation_date, modification_date, publish_date, title, subtitle, slug, body, formated_body, excerpt, score, num_views, metadata)
VALUES (:user_id, :post_type_id, :status, :creation_date, :modification_date, :publish_date, :title, :subtitle, :slug, :body, :formated_body, :excerpt, :score, :num_views, :metadata)
EOD;
const SQL_SELECT_COMMENTS = "SELECT * FROM foro_mensajes WHERE id_hilo = :id_hilo";

const NEW_STATUS_DRAFT = 0;
const NEW_STATUS_PUBLISHED = 1;
const NEW_STATUS_DELETED = 2;
const OLD_STATUS_BORRADOR = 'borrador';
const OLD_STATUS_REVISAR = 'revisar';
const OLD_STATUS_PUBLICADO = 'publicado';
const OLD_STATUS_PRIVADO = 'privado';
const OLD_STATUS_PAPELERA = 'papelera';

function getPostTypeId($old_post_type) {
    return 1; //TODO: check this with BUS 
}

function getStatus($old_status) {
    switch($old_status) {
        case OLD_STATUS_BORRADOR:
        case OLD_STATUS_REVISAR:
        case OLD_STATUS_PRIVADO:
            return NEW_STATUS_DRAFT;
        break;
        case OLD_STATUS_PUBLICADO:
            return NEW_STATUS_PUBLISHED;
        break;
        case OLD_STATUS_PAPELERA:
            return NEW_STATUS_DELETED;
        break;
    }
}

print "-----STARTING POSTS MIGRATION -----" . PHP_EOL;

forech($db_old->query(SQL_SELECT_POSTS) as $post) {
    print "STARTING PROCESS FOR " . $post->titular . PHP_EOL;
    
    $select_user_new_id = $db_new->prepare(SQL_SELECT_USER_NEW_ID);
    $select_user_new_id->execute([
        ':old_id' => $post->creador
    ]);

    $new_user_id = $select_user_new_id->fetch(PDO::FETCH_OBJ)->new_id;
    $insert_fixed_post = $db_new->prepare(SQL_INSERT_FIXED_POST);
    $insert_fixed_post->execute([
        ':user_id' => $new_user_id,
        ':post_type_id' => getPostTypeId($post->tipo),
        ':status' => getStatus($post->estado),
        ':creation_date' => $post->fecha_publicacion,
        ':modification_date' => null,
        ':publish_date' => $post->fecha_publicacion,
        ':title' => $post->titular,
        ':subtitle' => $post->subtitular,
        ':slug' => $post->url,
        ':body' => null,
        ':formated_body' => $post->cuerpo,
        ':excerpt' => $post->extracto,
        ':score' => $post->nota_anait,
        ':num_views' => $post->numero_visitas,
        ':metadata' => null
    ]);
    
    $last_inserted_post_id = $db_new->lastInsertId();
    $select_comments = $dbo_old->prepare(SQL_SELECT_COMMENTS);
    $select_comments->execute([
        ':id_hilo' => $post->id_hilo_foro
    ]);
    foreach($select_comments->fetch(PDO::FETCH_OBJ) as $comment) {
        $select_comment_user_new_id = $db_new->prepare(SQL_SELECT_USER_NEW_ID);
        $select_comment_user_new_id->execute([
            ':old_id' => $comment->creador
        ]);
        $new_comment_user_id = $select_comment_user_new_id(PDO::FETCH_OBJ)->new_id;

        $insert_fixed_comment = $db_new->prepare(SQL_INSERT_COMMENT);
        $insert_fixed_comment->execute([
            ':post_id' => $last_inserted_post_id,
            ':user_id' => $new_comment_user_id,
            ':body' => $comment->formated_text,
            ':formated_body' => $comment->formated_text,
            'creation_date' => $comment->fecha,
            'modification_date' => $comment->fecha_edit
        ]);
    }
}

print "-----MIGRATION POSTS ENDED-----" . PHP_EOL;
