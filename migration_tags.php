#!/usr/bin/php

<?php
include config.php

const SQL_SELECT_PLATAFORMA = "SELECT * FROM plataforma LIMIT 50";
const SQL_SELECT_FICHA = "SELECT * FROM fichas LIMIT 50";
const SQL_SELECT_JUEGO = "SELECT * FROM juego LIMIT 50"
const SQL_INSERT_FIXED_TAG = "INSERT INTO tags (name, slug) VALUES (:name, :slug)";
const SQL_SELECT_PLATAFORMA_RELATIONSHIP = "SELECT * FROM articulo_plataforma WHERE id_plataforma = :id_plataforma";
const SQL_SELECT_FICHA_RELATIONSHIP = "SELECT * FROM articulo_fichas_relacionados WHERE id_ficha = :id_ficha";
const SQL_SELECT_JUEGO_RELATIONSHIP = "SELECT * FROM articulo_juegos_relacionados WHERE id_juego = :id_juego"
const SQL_SELECT_NEW_ARTICULO_ID = "SELECT id_new FROM _temp_post_id WHERE id_old = :id_old";
const SQL_INSERT_FIXED_TAG_RELATIONSHIP = "INSERT INTO posts_tags (post_id, tag_id) VALUES (:post_id, :tag_id)";

print "-----STARTING TAGS (platform) MIGRATION -----" . PHP_EOL;

foreach($db_old->query(SQL_SELECT_PLATAFORMA) as $platform) {
    $insert_fixed_tag = $db_new->prepare(SQL_INSERT_FIXED_TAG);
    $insert_fixed_tag->execute([
        ':name' => $platform->nombre,
        ':slug' => $platform->url
    ]);

    $tag_id = $db_new->lastInsertId();

    $select_relationships = $db_old->prepare(SQL_SELECT_PLATAFORMA_RELATIONSHIP);
    $select_relationships->execute([
        ':id_plataforma' => $platform->id
    ]);

    foreach($select_relationships->fetch(PDO::FETCH_OBJ as $relationship) {
        $select_post_new_id = $db_new->prepare(SQL_SELECT_NEW_ARTICULO_ID);
        $select_post_new_id->execute([
            ':id_old' => $relationship->id_articulo
        ]);
        $post_new_id = $select_post_new_id->fetch(PDO::FETCH_OBJ)->id_new;

        $insert_tag_relationship = $db_new->prepare(SQL_INSERT_FIXED_TAG_RELATIONSHIP);
        $insert_tag_relationship->execute([
            ':tag_id' => $tag_id,
            ':post_id' => $post_new_id
        ]);
    }
}

print "-----TAGS (plaform) MIGRATION ENDED-----" . PHP_EOL;

print "-----STARTING TAGS (ficha) MIGRATION -----" . PHP_EOL;

foreach($db_old->query(SQL_SELECT_FICHA) as $ficha) {
    $insert_fixed_tag = $db_new->prepare(SQL_INSERT_FIXED_TAG);
    $insert_fixed_tag->execute([
        ':name' => $ficha->nombre,
        ':slug' => $ficha->url
    ]);

    $tag_id = $db_new->lastInsertId();

    $select_relationships = $db_old->prepare(SQL_SELECT_FICHA_RELATIONSHIP);
    $select_relationships->execute([
        ':id_ficha' => $ficha->id
    ]);

    foreach($select_relationships->fetch(PDO::FETCH_OBJ as $relationship) {
        $select_post_new_id = $db_new->prepare(SQL_SELECT_NEW_ARTICULO_ID);
        $select_post_new_id->execute([
            ':id_old' => $relationship->id_articulo
        ]);
        $post_new_id = $select_post_new_id->fetch(PDO::FETCH_OBJ)->id_new;

        $insert_tag_relationship = $db_new->prepare(SQL_INSERT_FIXED_TAG_RELATIONSHIP);
        $insert_tag_relationship->execute([
            ':tag_id' => $tag_id,
            ':post_id' => $post_new_id
        ]);
    }
}

print "-----TAGS (ficha) MIGRATION ENDED-----" . PHP_EOL;

print "-----STARTING TAGS (juegos) MIGRATION -----" . PHP_EOL;

foreach($db_old->query(SQL_SELECT_JUEGO) as $juego) {
    $insert_fixed_tag = $db_new->prepare(SQL_INSERT_FIXED_TAG);
    $insert_fixed_tag->execute([
        ':name' => $juego->nombre,
        ':slug' => $juego->url
    ]);

    $tag_id = $db_new->lastInsertId();

    $select_relationships = $db_old->prepare(SQL_SELECT_JUEGO_RELATIONSHIP);
    $select_relationships->execute([
        ':id_juego' => $juego->id
    ]);

    foreach($select_relationships->fetch(PDO::FETCH_OBJ as $relationship) {
        $select_post_new_id = $db_new->prepare(SQL_SELECT_NEW_ARTICULO_ID);
        $select_post_new_id->execute([
            ':id_old' => $relationship->id_articulo
        ]);
        $post_new_id = $select_post_new_id->fetch(PDO::FETCH_OBJ)->id_new;

        $insert_tag_relationship = $db_new->prepare(SQL_INSERT_FIXED_TAG_RELATIONSHIP);
        $insert_tag_relationship->execute([
            ':tag_id' => $tag_id,
            ':post_id' => $post_new_id
        ]);
    }
}

print "-----TAGS (juegos) MIGRATION ENDED-----" . PHP_EOL;
