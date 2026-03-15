<?php
function audit_log(PDO $pdo, $userId, $action, $entityType = null, $entityId = null, $details = null)
{
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id, ip_address, user_agent, method, url, details, created_at)
        VALUES
            (:user_id, :action, :entity_type, :entity_id, INET6_ATON(:ip), :user_agent, :method, :url, :details, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId ?: null,
        ':action' => $action,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':ip' => client_ip(),
        ':user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ':method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        ':url' => mb_substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
        ':details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
    ]);
}
