<?php
// Banco descartável em socket separado. Não usa o MySQL do sistema.
$root=dirname(__DIR__);
if (is_file($root.'/config.php')) {
 $existing=require $root.'/config.php';
 if (($existing['dsn'] ?? '')!=='mysql:unix_socket='.$root.'/.test-data/mysql.sock;dbname=etiquetas_test;charset=utf8mb4') throw new RuntimeException('Configuração não pertence ao banco de teste.');
}
$pdo=new PDO('mysql:unix_socket='.$root.'/.test-data/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS etiquetas_test CHARACTER SET utf8mb4');
$pdo->exec('USE etiquetas_test');
$pdo->exec(file_get_contents($root.'/app/schema.sql'));
$pdo->exec("CREATE USER IF NOT EXISTS 'etiquetas_test'@'localhost' IDENTIFIED BY 'local-disposable-test'");
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON etiquetas_test.* TO 'etiquetas_test'@'localhost'");
$pdo->exec('DELETE FROM admins');
$pdo->exec('DELETE FROM contacts');
$pdo->exec('DELETE FROM login_attempts');
$pdo->prepare('INSERT INTO admins(name,email,password_hash) VALUES(?,?,?)')->execute(['Administrador','teste@example.com',password_hash('test-password-123', PASSWORD_DEFAULT)]);
file_put_contents($root.'/config.php','<?php return '.var_export(['dsn'=>'mysql:unix_socket='.$root.'/.test-data/mysql.sock;dbname=etiquetas_test;charset=utf8mb4','user'=>'etiquetas_test','password'=>'local-disposable-test'],true).';');
chmod($root.'/config.php',0600);
echo "Banco de teste preparado.\n";
