<?php
function is_ip_banned(PDO $pdo, $ip)
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM ip_bans
        WHERE ip_address = INET6_ATON(:ip)
          AND (is_permanent = 1 OR banned_until IS NULL OR banned_until > NOW())
        LIMIT 1
    ");
    $stmt->execute([':ip' => $ip]);
    return (bool)$stmt->fetchColumn();
}
