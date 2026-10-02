<?php
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/label-layout.php';

$page = (string)($_GET['page'] ?? 'contacts');
$error = '';
if (!isset($_SESSION['admin'])) {
 if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='login') {
  $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
  $fingerprint = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '').'|'.$email);
  $query=db()->prepare('SELECT * FROM login_attempts WHERE fingerprint=?'); $query->execute([$fingerprint]); $attempt=$query->fetch();
  if ($attempt && $attempt['attempts']>=8 && (int)$attempt['window_start']>time()-900) $error='Muitas tentativas. Aguarde 15 minutos e tente novamente.';
  else {
   $query=db()->prepare('SELECT * FROM admins WHERE email=?'); $query->execute([$email]); $admin=$query->fetch();
   $hash=$admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
   if (password_verify((string)($_POST['password'] ?? ''), $hash) && $admin) {
    db()->prepare('DELETE FROM login_attempts WHERE fingerprint=?')->execute([$fingerprint]);
    session_regenerate_id(true); $_SESSION['admin']=['id'=>$admin['id'], 'name'=>$admin['name'], 'email'=>$admin['email']]; $_SESSION['last_seen']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32)); redirect('/');
   }
   db()->prepare('INSERT INTO login_attempts(fingerprint,attempts,window_start) VALUES(?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(window_start < ?,1,attempts+1), window_start=IF(window_start < ?,?,window_start)')->execute([$fingerprint,time(),time()-900,time()-900,time()]);
   $error='E-mail ou senha incorretos.';
  }
 }
 require dirname(__DIR__).'/app/login.php'; exit;
}
$query=db()->prepare('SELECT settings FROM label_settings WHERE admin_id=?'); $query->execute([$_SESSION['admin']['id']]);
$savedDesign=$query->fetchColumn();
$layout=labelLayout($savedDesign?json_decode($savedDesign,true):($_SESSION['label_dimensions'] ?? []));
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  $action=$_POST['action'] ?? '';
  if (in_array($action,['save_design','reset_design'],true)) {
   $candidate=labelLayout($action==='reset_design'?labelDefaults():$_POST);
   $settings=array_intersect_key($candidate,labelDefaults());
   db()->prepare('INSERT INTO label_settings(admin_id,settings) VALUES(?,?) ON DUPLICATE KEY UPDATE settings=?')->execute([$_SESSION['admin']['id'],json_encode($settings),json_encode($settings)]);
   unset($_SESSION['label_dimensions']);
   $_SESSION['flash']=$action==='reset_design'?'Valores padrão restaurados e salvos.':'Design salvo. Suas próximas etiquetas usarão estas configurações.'; redirect('/?page=design');
  }
  if ($action==='logout') { $_SESSION=[]; session_destroy(); redirect('/'); }
  if ($action==='save') {
   $data=contactData($_POST); $id=filter_var($_POST['id'] ?? 0,FILTER_VALIDATE_INT);
   if ($id) {
    $query=db()->prepare('UPDATE contacts SET name=?, company=?, phone=?, email=? WHERE id=?'); $query->execute([$data['name'],$data['company'],$data['phone'],$data['email'],$id]);
   } else {
    $query=db()->prepare('INSERT INTO contacts(name,company,phone,email) VALUES(?,?,?,?)'); $query->execute([$data['name'],$data['company'],$data['phone'],$data['email']]);
   }
   $_SESSION['flash']='Cadastro salvo com sucesso.'; redirect('/');
  }
  if ($action==='delete') { db()->prepare('DELETE FROM contacts WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]); $_SESSION['flash']='Cadastro excluído.'; redirect('/'); }
  if ($action==='password') {
   $query=db()->prepare('SELECT password_hash FROM admins WHERE id=?'); $query->execute([$_SESSION['admin']['id']]);
   if (!password_verify((string)($_POST['current'] ?? ''),$query->fetchColumn())) throw new InvalidArgumentException('A senha atual está incorreta.');
   $new=(string)($_POST['password'] ?? '');
   if (strlen($new)<12 || strlen($new)>72) throw new InvalidArgumentException('Use uma senha entre 12 e 72 caracteres.');
   if ($new!==($_POST['confirm'] ?? '')) throw new InvalidArgumentException('As senhas novas não coincidem.');
   db()->prepare('UPDATE admins SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['admin']['id']]);
   session_regenerate_id(true); $_SESSION['flash']='Senha atualizada.'; redirect('/?page=settings');
  }
 } catch (InvalidArgumentException $ex) { $error=$ex->getMessage(); if (($action ?? '')==='save') $page='form'; if (($action ?? '')==='save_design') $page='design'; }
}
if ($page==='print') {
 if ($_SERVER['REQUEST_METHOD']!=='POST') redirect('/?page=labels');
 $ids=array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])),fn($id)=>$id>0)));
 $copies=filter_var($_POST['copies'] ?? 1,FILTER_VALIDATE_INT);
 if (!$ids || count($ids)>200 || !$copies || $copies<1 || $copies>100 || count($ids)*$copies>500) { $error='Selecione os cadastros e gere no máximo 500 etiquetas por vez.'; $page='labels'; }
 else {
  $query=db()->prepare('SELECT * FROM contacts WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY name'); $query->execute($ids); $labels=$query->fetchAll();
  if (!$labels) { $error='Nenhum cadastro encontrado.'; $page='labels'; } else { require dirname(__DIR__).'/app/print.php'; exit; }
 }
}
$flash=$_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$stats=db()->query('SELECT COUNT(*) total, COUNT(DISTINCT company) companies, SUM(created_at >= DATE_FORMAT(CURRENT_DATE(), "%Y-%m-01")) recent FROM contacts')->fetch();
$search=mb_substr(trim((string)($_GET['q'] ?? '')),0,190);
$where=$search!==''?' WHERE name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?':'';
$params=$search!==''?array_fill(0,4,'%'.$search.'%'):[];
$query=db()->prepare('SELECT COUNT(*) FROM contacts'.$where); $query->execute($params); $total=(int)$query->fetchColumn();
$current=max(1,min((int)($_GET['p'] ?? 1),max(1,(int)ceil($total/20)))); $offset=($current-1)*20;
$query=db()->prepare('SELECT * FROM contacts'.$where.' ORDER BY created_at DESC, id DESC LIMIT 20 OFFSET '.$offset); $query->execute($params); $contacts=$query->fetchAll();
$contact=['id'=>'','name'=>'','company'=>'','phone'=>'','email'=>''];
if ($page==='form') {
 if ($error) $contact=array_merge($contact,array_intersect_key($_POST,$contact));
 elseif (!empty($_GET['id'])) { $query=db()->prepare('SELECT * FROM contacts WHERE id=?'); $query->execute([(int)$_GET['id']]); $contact=$query->fetch(); if ($contact) $contact['phone']='+' . $contact['phone']; if (!$contact) { http_response_code(404); $error='Cadastro não encontrado.'; $page='contacts'; } }
}
$titles=['contacts'=>'Empresas e contatos','labels'=>'Gerar etiquetas','form'=>empty($contact['id'])?'Novo cadastro':'Editar cadastro','settings'=>'Minha conta','design'=>'Design da etiqueta'];
$title=$titles[$page] ?? $titles['contacts'];
require dirname(__DIR__).'/app/dashboard.php';
