# Etiquetas

Plataforma administrativa em português para cadastrar pessoas e empresas e imprimir etiquetas com QR Code para WhatsApp. PHP 8.5, MySQL 8.4, Nginx e endroid/qr-code (SVG local, sem enviar contatos a serviços de QR).

## Instalar neste ambiente

Após clonar o repositório, execute na pasta do projeto. O ambiente precisa de PHP 8.5, Composer, MySQL, Nginx e do comando `criar-projeto` configurado.

```bash
composer install --no-dev
sudo bash bin/install.sh
```

Instala em `/var/www/etiquetas`, cria o virtual host com `criar-projeto`, banco `etiquetas` e usuário `etiquetas_app` com SELECT/INSERT/UPDATE/DELETE, valida Nginx e HTTP. Apenas `public/` é servido. A senha do banco fica em `config.php` fora da pasta pública, com permissão 0640, ignorado pelo Git. O instalador não substitui diretórios ou bancos preexistentes desconhecidos.

O instalador mostra a senha inicial aleatória da conta `admin@etiquetas.br`. Altere-a em **Minha conta**. Não há senha padrão nem cadastro público de administradores.

No Windows, execute `bin/windows-hosts.ps1` em um PowerShell como administrador, ou acrescente `127.0.0.1 etiquetas.br` a `C:\Windows\System32\drivers\etc\hosts`. O hosts Linux é configurado pelo instalador. O endereço é local, sem registro de DNS público.

## Usar

1. Entre em `http://etiquetas.br`.
2. Em **Novo cadastro**, informe nome da pessoa, empresa, telefone com DDD e e-mail.
3. Em **Design da etiqueta**, configure largura e altura, fonte, tamanhos dos três textos, modelos de texto, tipo de folha (A4, A5, Carta ou personalizado), orientação, margens e espaçamento. Campos disponíveis nos textos: `{nome}`, `{empresa}`, `{telefone}` e `{email}`. Texto secundário e rodapé vazios ficam ocultos. As medidas da folha aparecem apenas em **Personalizado**; os demais formatos usam as dimensões padrão, respeitando a orientação. Clique em **Salvar design**: as preferências são armazenadas no MySQL por administrador e permanecem após sair da conta.
4. Em **Gerar etiquetas**, selecione os contatos, defina as cópias e abra a pré-visualização. A impressão usa o design salvo. Máximo de 500 etiquetas por geração; seleção limitada à página atual.
5. Use **Imprimir / Salvar PDF**, escala 100%, sem cabeçalho e rodapé. Escolha no diálogo de impressão a mesma folha e orientação configuradas. Para papéis personalizados, a impressora e seu driver precisam oferecer as medidas escolhidas. O padrão permanece 90 × 35 mm em A4, 14 etiquetas por folha.

A prévia usa dados ilustrativos. Textos longos podem precisar de fonte menor ou etiqueta maior; a prévia e a tela de impressão sinalizam transbordamento. QR Code fixo à direita, ajustado às dimensões da etiqueta.

Telefones brasileiros com 10 ou 11 dígitos recebem o código 55. Outros países devem ser informados com `+` e código do país. O QR contém `https://wa.me/NUMERO`; a conversa depende de esse número ter WhatsApp. Etiquetas já impressas não mudam se o cadastro for editado ou excluído.

Busca por pessoa, empresa, e-mail e telefone; edição e exclusão com confirmação; paginação; proteção CSRF; sessões HTTP-only com expiração por inatividade; senhas com hash; limitação de tentativas de login por combinação de IP e e-mail; SQL parametrizado e escape de HTML.

## Escopo

Aplicação de uma plataforma com administrador e cadastro centralizado. Não inclui cobrança de assinaturas, isolamento de contas de clientes nem acesso individual para empresas. Para publicar na internet, configurar domínio público, HTTPS, backups e entrega de credenciais adequada ao ambiente.

## Verificação

`tests/integration.py` verifica o fluxo HTTP contra um banco descartável: login, CSRF, validação, criação, edição, busca, etiquetas, alteração de senha e exclusão. Nunca execute a preparação do banco de teste contra dados de produção. Dependências PHP fixadas no `composer.lock`; instalar com `composer install --no-dev`.
