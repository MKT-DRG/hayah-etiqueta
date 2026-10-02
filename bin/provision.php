<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
$root=dirname(__DIR__);
if (is_file($root.'/config.php')) { echo "Configuração existente preservada.\n"; exit; }
$pdo=new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$exists=$pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='etiquetas'")->fetchColumn();
$userExists=$pdo->query("SELECT User FROM mysql.user WHERE User='etiquetas_app'")->fetchColumn();
if ($exists || $userExists) throw new RuntimeException('Banco ou usuário já existe. Revise antes de instalar para preservar os dados.');
$password=bin2hex(random_bytes(24));
$pdo->exec('CREATE DATABASE etiquetas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec("CREATE USER 'etiquetas_app'@'localhost' IDENTIFIED BY ".$pdo->quote($password));
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON etiquetas.* TO 'etiquetas_app'@'localhost'");
$pdo->exec('USE etiquetas');
$pdo->exec(file_get_contents($root.'/app/schema.sql'));
$adminPassword=bin2hex(random_bytes(10));
$pdo->prepare('INSERT INTO admins(name,email,password_hash) VALUES(?,?,?)')->execute(['Administrador','admin@etiquetas.br',password_hash($adminPassword,PASSWORD_DEFAULT)]);
$config=['dsn'=>'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=etiquetas;charset=utf8mb4','user'=>'etiquetas_app','password'=>$password];
file_put_contents($root.'/config.php',"<?php\nreturn ".var_export($config,true).";\n", LOCK_EX);
chmod($root.'/config.php',0640);
chgrp($root.'/config.php','www-data');
echo "\nLOGIN INICIAL\nE-mail: admin@etiquetas.br\nSenha: ".$adminPassword."\nGuarde esta senha e altere em Minha conta após entrar.\n\n";
