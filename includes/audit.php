<?php
function audit_log($user_id, $action, $entity_type = null, $entity_id = null, $details = null)
{
    try {
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, method, url, details, created_at) VALUES (:u,:a,:et,:ei,:ip,:ua,:m,:url,:d,NOW())");
        $stmt->execute([
            ':u'=>$user_id, ':a'=>$action, ':et'=>$entity_type, ':ei'=>$entity_id,
            ':ip'=>$_SERVER['REMOTE_ADDR'] ?? '', ':ua'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255),
            ':m'=>$_SERVER['REQUEST_METHOD'] ?? 'GET', ':url'=>substr($_SERVER['REQUEST_URI'] ?? '',0,255),
            ':d'=>$details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
        ]);
    } catch (Throwable $e) {}
}
