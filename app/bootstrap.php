<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');
session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off', 'use_strict_mode'=>true]);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function db(): PDO {
 static $db;
 if (!$db) {
  $config = require dirname(__DIR__).'/config.php';
  $db = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
 }
 return $db;
}
function phone(string $input): string {
 $digits = preg_replace('/\D/', '', $input);
 if (!str_starts_with(trim($input), '+') && in_array(strlen($digits), [10,11], true)) $digits = '55'.$digits;
 if (!preg_match('/^[1-9][0-9]{9,14}$/', $digits)) throw new InvalidArgumentException('Informe o WhatsApp com DDD. Para outros países, use + e o código do país.');
 return $digits;
}
function contactData(array $input): array {
 $data = [];
 foreach (['name'=>120, 'company'=>160, 'email'=>190] as $key=>$max) {
  $data[$key] = trim((string)($input[$key] ?? ''));
  if ($data[$key]==='' || mb_strlen($data[$key])>$max) throw new InvalidArgumentException('Preencha nome, empresa e e-mail respeitando o tamanho indicado.');
 }
 if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');
 $data['phone'] = phone((string)($input['phone'] ?? ''));
 return $data;
}
function qr(string $phone): string {
 $builder = new Endroid\QrCode\Builder\Builder(writer: new Endroid\QrCode\Writer\SvgWriter(), data: 'https://wa.me/'.$phone, size: 300, margin: 48);
 return $builder->build()->getDataUri();
}
set_exception_handler(function(Throwable $error): void {
 error_log((string)$error);
 http_response_code(500);
 echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Etiquetas • Configuração</title><link rel="stylesheet" href="/assets/app.css"><main class="setup"><h1>Não foi possível abrir a plataforma</h1><p>Verifique a instalação e a conexão com o banco de dados. Os detalhes estão no log do servidor.</p><a href="/">Tentar novamente</a></main></html>';
});
if ($_SERVER['REQUEST_METHOD']==='POST' && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))) {
 http_response_code(403); exit('Sessão expirada. Recarregue a página e tente novamente.');
}
if (isset($_SESSION['admin']) && time()-($_SESSION['last_seen'] ?? 0)>7200) unset($_SESSION['admin']);
if (isset($_SESSION['admin'])) $_SESSION['last_seen']=time();
