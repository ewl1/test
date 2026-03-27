<?php
function get_posts(PDO $pdo, $limit = null)
{
    $limit = $limit ?: post_limit_setting($pdo);
    $stmt = $pdo->prepare("
        SELECT p.*, u.username
        FROM posts p
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_post(PDO $pdo, $id)
{
    $stmt = $pdo->prepare("
        SELECT p.*, u.username
        FROM posts p
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function save_post(PDO $pdo, array $data, $id = null)
{
    $title = trim($data['title'] ?? '');
    $content = clean_html($data['content'] ?? '');
    $status = in_array(($data['status'] ?? 'draft'), ['draft','published'], true) ? $data['status'] : 'draft';

    if ($title === '' || $content === '') {
        return [false, 'Užpildykite pavadinimą ir turinį.'];
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE posts SET title=:title, content=:content, status=:status, updated_at=NOW() WHERE id=:id");
        $stmt->execute([':title'=>$title, ':content'=>$content, ':status'=>$status, ':id'=>$id]);
        audit_log($pdo, $_SESSION['user']['id'] ?? null, 'post_update', 'posts', $id);
        return [true, 'Postas atnaujintas.'];
    }

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, status, created_at, updated_at) VALUES (:user_id, :title, :content, :status, NOW(), NOW())");
    $stmt->execute([':user_id'=>$_SESSION['user']['id'] ?? null, ':title'=>$title, ':content'=>$content, ':status'=>$status]);
    $newId = (int)$pdo->lastInsertId();
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'post_create', 'posts', $newId);
    return [true, 'Postas sukurtas.'];
}

function delete_post(PDO $pdo, $id)
{
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    audit_log($pdo, $_SESSION['user']['id'] ?? null, 'post_delete', 'posts', $id);
}
