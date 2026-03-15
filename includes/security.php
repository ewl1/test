<?php
function csrf_token(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(){ return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">'; }
function verify_csrf(){ if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf_token'] ?? '')) { http_response_code(419); die('Neteisingas CSRF token.'); } }
function e($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function flash($key, $value = null){ if ($value !== null) { $_SESSION['_flash'][$key] = $value; return null; } if (!isset($_SESSION['_flash'][$key])) return null; $tmp = $_SESSION['_flash'][$key]; unset($_SESSION['_flash'][$key]); return $tmp; }
