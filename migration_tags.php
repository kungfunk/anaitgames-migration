#!/usr/bin/php

<?php
include config.php

const SQL_SELECT_PLATAFORMA = "SELECT * FROM plataforma LIMIT 50";
const SQL_INSERT_FIXED_PLATAFORMA = "INSERT INTO tags (name, slug) VALUES (:name, :slug)";
const SQL_SELECT_PLATAFORMA_RELATIONSHIP = "SELECT * FROM articulo_plataforma WHERE id_plataforma = :id_plataforma";
const SQL_SELECT_NEW_ARTICULO_ID = "SELECT id_new FROM _temp_post_id WHERE id_old = :id_old";
const SQL_INSERT_FIXED_PLATAFORMA_RELATIONSHIP = "INSERT INTO posts_tags (post_id, tag_id) VALUES (:post_id, :tag_id)";

print "-----STARTING TAGS (platform) MIGRATION -----" . PHP_EOL;

foreach($db_old->query(SQL_SELECT_PLATAFORMA) as $platform) {
    $insert_fixed_platform = $db_new->prepare(SQL_INSERT_FIXED_PLATAFORMA);
    $insert_fixed_platform->execute([
        ':name' => $platform->nombre,
        ':slug' => $platform->url
    ]);

    $platform_new_id = $db_new->lastInsertId();

    $platform_relationship = $db_old->prepare(SQL_SELECT_PLATAFORMA_RELATIONSHIP);
    $platform_relationship->execute([
        ':id_plataforma' => $platform->id
    ]);

    foreach($platform_relationship->fetch(PDO::FETCH_OBJ as $relationship) {
        $select_post_new_id = $db_new->prepare(SQL_SELECT_NEW_ARTICULO_ID);
        $select_post_new_id->execute([
            ':'
        ]);

        $insert_platform_fixed_relationship = $db_new->prepare(SQL_INSERT_FIXED_PLATAFORMA_RELATIONSHIP);
        $insert_platform_fixed_relationship->execute([
            ':tag_id' => $platform_new_id,
            ':post_id' => $post_new_id
        ]);
    }
}

print "-----TAGS (plaform) MIGRATION ENDED-----" . PHP_EOL;
