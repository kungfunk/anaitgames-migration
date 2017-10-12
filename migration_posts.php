<?php
include 'config.php';

const SQL_SELECT_POSTS = "SELECT * FROM articulo WHERE estado NOT IN ('papelera', 'borrador')";
const SQL_SELECT_USER_NEW_ID = "SELECT new_id FROM _temp_user_id WHERE old_id = :old_id";
const SQL_INSERT_FIXED_POST = <<<EOD
INSERT INTO posts (user_id, post_type_id, status, creation_date, modification_date, publish_date, title, subtitle, slug, body, formated_body, excerpt, original_author, score, num_views, metadata)
VALUES (:user_id, :post_type_id, :status, :creation_date, :modification_date, :publish_date, :title, :subtitle, :slug, :body, :formated_body, :excerpt, :original_author, :score, :num_views, :metadata)
EOD;
const SQL_SELECT_COMMENTS = "SELECT * FROM foro_mensajes WHERE id_hilo = :id_hilo";
const SQL_INSERT_TEMP_POST_ID = "INSERT INTO _temp_post_id (old_id, new_id) VALUES (:old_id, :new_id)";
const SQL_INSERT_COMMENT = <<<EOD
INSERT INTO comments (post_id, user_id, body, formated_body, creation_date, modification_date)
VALUES (:post_id, :user_id, :body, :formated_body, :creation_date, :modification_date)
EOD;

const NEW_STATUS_DRAFT = 0;
const NEW_STATUS_PUBLISHED = 1;
const NEW_STATUS_DELETED = 2;
const OLD_STATUS_BORRADOR = 'borrador';
const OLD_STATUS_REVISAR = 'revisar';
const OLD_STATUS_PUBLICADO = 'publicado';
const OLD_STATUS_PRIVADO = 'privado';
const OLD_STATUS_PAPELERA = 'papelera';
const NEW_TYPE_NEWS = 1;
const NEW_TYPE_ARTICLE = 2;
const NEW_TYPE_ANALYSIS = 3;
const NEW_TYPE_STREAMING = 4;
const NEW_TYPE_PODCAST = 5;
const OLD_TYPE_ARTICULO = 'articulo';
const OLD_TYPE_PRIMERAS_IMPRESIONES = 'primeras impresiones';
const OLD_TYPE_AVANCE = 'avance';
const OLD_TYPE_ANALISIS = 'analisis';
const OLD_TYPE_NOTICIA = 'noticia';
const OLD_TYPE_OPINION = 'opinion';
const OLD_TYPE_BLOG_EDITOR = 'blog de editor';
const OLD_TYPE_BLOG_USUARIO = 'blog de usuario';
const OLD_TYPE_EDITIORIAL = 'editorial';
const OLD_TYPE_GALERIA = 'galeria de imagenes';
const OLD_TYPE_VIDEO = 'video';
const OLD_TYPE_ENTREVISTA = 'entrevista';
const OLD_TYPE_ENCUESTA = 'encuesta';
const OLD_TYPE_CONCURSO = 'concurso';
const OLD_TYPE_SORTEO = 'sorteo';
const OLD_TYPE_COBERTURA = 'cobertura en directo';
const OLD_TYPE_PODCAST = 'podcast';
const DEFAULT_AUTHOR_ID = 1;

function getPostTypeId($old_post_type) {
    switch($old_post_type) {
        case OLD_TYPE_NOTICIA:
        case OLD_TYPE_VIDEO:
        case OLD_TYPE_CONCURSO:
        case OLD_TYPE_ENCUESTA:
        case OLD_TYPE_GALERIA:
        case OLD_TYPE_SORTEO:
            return NEW_TYPE_NEWS;
        break;
        case OLD_TYPE_ARTICULO:
        case OLD_TYPE_PRIMERAS_IMPRESIONES:
        case OLD_TYPE_AVANCE:
        case OLD_TYPE_OPINION:
        case OLD_TYPE_BLOG_EDITOR:
        case OLD_TYPE_BLOG_USUARIO:
        case OLD_TYPE_EDITIORIAL:
        case OLD_TYPE_ENTREVISTA:
            return NEW_TYPE_ARTICLE;
        break;
        case OLD_TYPE_ANALISIS:
            return NEW_TYPE_ANALYSIS;
        break;
        case OLD_TYPE_COBERTURA:
            return NEW_TYPE_STREAMING;
        break;
        case OLD_TYPE_PODCAST:
            return NEW_TYPE_PODCAST;
        break;
    }
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

foreach($db_old->query(SQL_SELECT_POSTS) as $post) {
    print "STARTING PROCESS FOR " . $post->titular . " (" .$post->fecha_publicacion . ")" . PHP_EOL;

    if($post->creador == 0) {
        $new_user_id = DEFAULT_AUTHOR_ID;
    }
    else {
        $select_user_new_id = $db_new->prepare(SQL_SELECT_USER_NEW_ID);
        $select_user_new_id->execute([
            ':old_id' => $post->creador
        ]);
        $new_user_id = $select_user_new_id->fetch(PDO::FETCH_OBJ)->new_id;
    }

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
        ':original_author' => $post->otro,
        ':score' => $post->nota_anait,
        ':num_views' => $post->numero_visitas,
        ':metadata' => null
    ]);
    
    $last_inserted_post_id = $db_new->lastInsertId();

    $insert_temp_post_id = $db_new->prepare(SQL_INSERT_TEMP_POST_ID);
    $insert_temp_post_id->execute([
        ':old_id' => $post->id,
        ':new_id' => $last_inserted_post_id
    ]);

    $select_comments = $db_old->prepare(SQL_SELECT_COMMENTS);
    $select_comments->execute([
        ':id_hilo' => $post->id_foro_hilo
    ]);

    foreach($select_comments->fetchAll(PDO::FETCH_OBJ) as $comment) {
        $select_comment_user_new_id = $db_new->prepare(SQL_SELECT_USER_NEW_ID);
        $select_comment_user_new_id->execute([
            ':old_id' => $comment->id_usuario
        ]);
        $new_comment_user_id = $select_comment_user_new_id->fetch(PDO::FETCH_OBJ)->new_id;

        if($new_comment_user_id) {
            $insert_fixed_comment = $db_new->prepare(SQL_INSERT_COMMENT);
            $insert_fixed_comment->execute([
                ':post_id' => $last_inserted_post_id,
                ':user_id' => $new_comment_user_id,
                ':body' => $comment->texto,
                ':formated_body' => $comment->texto,
                ':creation_date' => $comment->fecha,
                ':modification_date' => null
            ]);
        }
    }
}

print "-----MIGRATION POSTS ENDED-----" . PHP_EOL;
