<?php
function rate_limit_check(PDO $pdo, $actionKey, $maxAttempts = 5, $windowSeconds = 900)
{
    $ip = client_ip();
    $stmt = $pdo->prepare("
        SELECT id, attempts, window_start
        FROM rate_limits
        WHERE ip_address = INET6_ATON(:ip)
          AND action_key = :action_key
        LIMIT 1
    ");
    $stmt->execute([':ip' => $ip, ':action_key' => $actionKey]);
    $row = $stmt->fetch();

    if (!$row) {
        $ins = $pdo->prepare("
            INSERT INTO rate_limits (ip_address, action_key, attempts, window_start, last_attempt)
            VALUES (INET6_ATON(:ip), :action_key, 1, NOW(), NOW())
        ");
        $ins->execute([':ip' => $ip, ':action_key' => $actionKey]);
        return true;
    }

    if ((time() - strtotime($row['window_start'])) > $windowSeconds) {
        $up = $pdo->prepare("UPDATE rate_limits SET attempts = 1, window_start = NOW(), last_attempt = NOW() WHERE id = :id");
        $up->execute([':id' => $row['id']]);
        return true;
    }

    if ((int)$row['attempts'] >= $maxAttempts) {
        return false;
    }

    $up = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE id = :id");
    $up->execute([':id' => $row['id']]);
    return true;
}
