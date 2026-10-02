<?php
declare(strict_types=1);
function labelDefaults(): array {
 return ['width'=>90,'height'=>35,'paper'=>'a4','orientation'=>'portrait','paper_width'=>210,'paper_height'=>297,'margin_x'=>12,'margin_y'=>15,'gap_x'=>6,'gap_y'=>3,'font'=>'arial','name_size'=>12,'company_size'=>9,'caption_size'=>6,'name_text'=>'{nome}','company_text'=>'{empresa}','caption_text'=>'VAMOS CONVERSAR?'];
}
function labelFonts(): array { return ['arial'=>'Arial, sans-serif','verdana'=>'Verdana, sans-serif','georgia'=>'Georgia, serif','courier'=>'"Courier New", monospace']; }
function labelLayout(array $input): array {
 $d=labelDefaults();
 // Standard paper dimensions are authoritative, even if stale custom values are posted.
 $paper=$input['paper'] ?? $d['paper'];
 $standard=is_string($paper) ? (['a4'=>[210,297],'a5'=>[148,210],'letter'=>[215.9,279.4]][$paper] ?? null) : null;
 if ($standard!==null) [$input['paper_width'],$input['paper_height']]=$standard;
 foreach (['width'=>[50,580],'height'=>[25,580],'paper_width'=>[70,600],'paper_height'=>[70,600],'margin_x'=>[0,40],'margin_y'=>[0,40],'gap_x'=>[0,20],'gap_y'=>[0,20],'name_size'=>[6,32],'company_size'=>[6,24],'caption_size'=>[5,18]] as $key=>[$min,$max]) {
  $value=$input[$key] ?? $d[$key];
  if (!is_scalar($value) || !preg_match('/^\d{1,3}(?:[.,]\d)?$/D',(string)$value)) throw new InvalidArgumentException('Informe medidas e tamanhos com no máximo uma casa decimal.');
  $value=(float)str_replace(',','.',(string)$value);
  if ($value<$min || $value>$max) throw new InvalidArgumentException("Valor fora do intervalo permitido: $key ($min a $max).");
  $d[$key]=$value;
 }
 foreach (['paper'=>['a4','a5','letter','custom'],'orientation'=>['portrait','landscape'],'font'=>array_keys(labelFonts())] as $key=>$options) {
  $value=$input[$key] ?? $d[$key];
  if (!in_array($value,$options,true)) throw new InvalidArgumentException('Escolha uma folha, orientação e fonte válidas.');
  $d[$key]=$value;
 }
 foreach (['name_text'=>160,'company_text'=>200,'caption_text'=>100] as $key=>$limit) {
  $value=$input[$key] ?? $d[$key];
  if (!is_string($value) || mb_strlen($value)>$limit) throw new InvalidArgumentException('O texto ultrapassou o limite permitido.');
  if ($key==='name_text' && trim($value)==='') throw new InvalidArgumentException('Preencha o texto principal.');
  preg_match_all('/\{[^{}]*\}/u',$value,$tokens);
  foreach ($tokens[0] as $token) if (!in_array($token,['{nome}','{empresa}','{telefone}','{email}'],true)) throw new InvalidArgumentException('Use apenas {nome}, {empresa}, {telefone} ou {email} como campos dinâmicos.');
  $d[$key]=trim($value);
 }
 [$pw,$ph]=match($d['paper']) {'a4'=>[210,297],'a5'=>[148,210],'letter'=>[215.9,279.4],default=>[$d['paper_width'],$d['paper_height']]};
 if ($d['orientation']==='landscape') [$pw,$ph]=[$ph,$pw];
 $d['sheet_width']=$pw; $d['sheet_height']=$ph;
 $d['columns']=(int)floor(($pw-2*$d['margin_x']+$d['gap_x']+0.00001)/($d['width']+$d['gap_x']));
 $d['rows']=(int)floor(($ph-2*$d['margin_y']+$d['gap_y']+0.00001)/($d['height']+$d['gap_y']));
 if ($d['columns']<1 || $d['rows']<1) throw new InvalidArgumentException('A etiqueta não cabe na folha com essas margens. Ajuste o tamanho, a orientação ou as margens.');
 $d['perSheet']=$d['columns']*$d['rows'];
 $d['qr']=round(min($d['height']-6,$d['width']*.4),1);
 return $d;
}
function labelText(string $template, array $contact): string {
 return strtr($template,['{nome}'=>$contact['name'],'{empresa}'=>$contact['company'],'{telefone}'=>'+'.$contact['phone'],'{email}'=>$contact['email']]);
}
function labelCss(array $d): string {
 $css='--label-font:'.labelFonts()[$d['font']].';';
 foreach (['label-width'=>'width','label-height'=>'height','qr-size'=>'qr','sheet-width'=>'sheet_width','sheet-height'=>'sheet_height','margin-x'=>'margin_x','margin-y'=>'margin_y','gap-x'=>'gap_x','gap-y'=>'gap_y'] as $name=>$key) $css.="--$name:".$d[$key].'mm;';
 foreach (['name-size'=>'name_size','company-size'=>'company_size','caption-size'=>'caption_size'] as $name=>$key) $css.="--$name:".$d[$key].'pt;';
 return $css.'--columns:'.$d['columns'].';';
}
function paperName(array $d): string { return ['a4'=>'A4','a5'=>'A5','letter'=>'Carta','custom'=>'Personalizada'][$d['paper']]; }
