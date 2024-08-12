<?php

/**
 * @throws Exception
 */
function validateArguments($argc, $argv): ?array
{
    if ($argc < 2 || $argc > 4) {
        throw new Exception("Invalid number of arguments\n", 1);
    }

    $id = $argv[$argc - 1];
    $options = getopt("", ["only:", "include-posts:"]);
    $only = null;
    $includePosts = null;

    // Validate and assign options
    if (isset($options['only'])) {
        $only = $options['only'];
        if (!in_array($only, ['instagram', 'tiktok'])) {
            throw new Exception("Invalid value for --only. Expected 'instagram' or 'tiktok'.\n", 1);
        }
    }

    if (isset($options['include-posts'])) {
        $includePosts = (int)$options['include-posts'];
        if ($includePosts <= 0) {
            throw new Exception("Invalid value for --include-posts. Must be a positive integer.\n", 1);
        }
    }
    return [
        'id' => $id,
        'only' => $only,
        'includePosts' => $includePosts
    ];
}

function dbConnection(string $dbEnv = 'dev'): PDO
{
    $dbProd = [
        'host' => 'localhost',
        'port' => '5432',
        'user' => 'postgres',
        'password' => 'root',
        'database' => 'storyclash_prod'
    ];

    $dbDev = [
        'host' => 'localhost',
        'port' => '5432',
        'user' => 'postgres',
        'password' => 'root',
        'database' => 'storyclash_dev'
    ];

    if ($dbEnv === 'prod') {
        return new PDO("pgsql:host={$dbProd['host']};port={$dbProd['port']};dbname={$dbProd['database']};user={$dbProd['user']};password={$dbProd['password']}");
    } else {
        return new PDO("pgsql:host={$dbDev['host']};port={$dbDev['port']};dbname={$dbDev['database']};user={$dbDev['user']};password={$dbDev['password']}");
    }
}

function copyEntries(array $args, PDO $prodPdo, PDO $devPdo)
{
    $devPdo->beginTransaction();
    try {
        $stmt = $devPdo->prepare("DELETE FROM feeds WHERE name = :name");
        $stmt->execute(['name' => $args['id']]);

        $stmt = $prodPdo->prepare("SELECT * FROM feeds WHERE name = :name");
        $stmt->execute(['name' => $args['id']]);
        $feed = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$feed) {
            echo "No feed found with name: {$args['id']}\n";
            exit(1);
        }
        $stmt = $devPdo->prepare("INSERT INTO feeds (name) VALUES (:name )");
        $stmt->execute(['name' => $feed['name']]);
        $insertedFeedId = $devPdo->lastInsertId();

        if ($args['only'] == 'instagram' || $args['only'] == null) {
            $stmt = $prodPdo->prepare("SELECT * FROM instagram_sources WHERE id = :id");

            $stmt->execute(['id' => $feed['id']]);
            $instagramPosts = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $devPdo->prepare("INSERT INTO instagram_sources (id, name, fan_count) VALUES (:feed_id, :name, :fan_count)");
            $stmt->execute([
                'feed_id' => $insertedFeedId,
                'name' => $instagramPosts['name'],
                'fan_count' => $instagramPosts['fan_count']
            ]);
        }

        if ($args['only'] == 'tiktok' || $args['only'] == null) {
            $stmt = $prodPdo->prepare("SELECT * FROM tiktok_sources WHERE id = :id");
            $stmt->execute(['id' => $feed['id']]);
            $tiktokPosts = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $devPdo->prepare("INSERT INTO tiktok_sources (id, name, fan_count) VALUES (:feed_id, :name, :fan_count)");
            $stmt->execute([
                'feed_id' => $insertedFeedId,
                'name' => $tiktokPosts['name'],
                'fan_count' => $tiktokPosts['fan_count']
            ]);
        }

        //copy from posts number of posts with for the given pid from prod to dev
        $querySelectPosts = "SELECT * FROM posts WHERE feed_id = :feed_id";
        if ($args['includePosts']) {
            $querySelectPosts .= " LIMIT :limit";
        }
        $stmt = $prodPdo->prepare($querySelectPosts);
        if ($args['includePosts']) {
            $stmt->bindValue('limit', $args['includePosts'], PDO::PARAM_INT);
        }

        $stmt->bindValue('feed_id', $feed['id'], PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($posts)) {
            $postsToInsert = [];
            foreach ($posts as $post) {
                $url = $post['url'];
                $postsToInsert[] = "($insertedFeedId, '$url')";
            }
            $query = "INSERT INTO posts (feed_id, url) VALUES " . implode(',', $postsToInsert);
            $devPdo->exec($query);
        }

        $devPdo->commit();
        echo "Entries copied successfully";
    } catch (Exception $e) {
        $devPdo->rollBack();
        throw $e;
    }
}
