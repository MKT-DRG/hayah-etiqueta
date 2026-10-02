#!/usr/bin/env bash
set -euo pipefail
if [[ ${EUID} -ne 0 ]]; then
  echo 'Execute: sudo bash /var/www/etiquetas/bin/install.sh'
  exit 1
fi
source_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
target_dir=/var/www/etiquetas
if [[ ! -f "$source_dir/vendor/autoload.php" ]]; then
  echo 'Execute composer install na pasta do projeto antes de instalar.'
  exit 1
fi
if [[ -e "$target_dir" && ! -f "$target_dir/.etiquetas-managed" ]]; then
  echo 'O diretório /var/www/etiquetas já existe e não pertence a este instalador. Nenhum arquivo foi substituído.'
  exit 1
fi
systemctl start mysql php8.5-fpm nginx
if [[ ! -d "$target_dir" ]]; then
  criar-projeto etiquetas
  touch "$target_dir/.etiquetas-managed"
fi
if [[ "$source_dir" != "$target_dir" ]]; then
  for directory in app public bin vendor; do
    cp -a "$source_dir/$directory" "$target_dir/"
  done
  cp "$source_dir/composer.json" "$source_dir/composer.lock" "$source_dir/README.md" "$source_dir/.gitignore" "$target_dir/"
fi
if [[ ! -e /etc/nginx/sites-available/etiquetas.br ]]; then
  cat > /etc/nginx/sites-available/etiquetas.br <<'NGINX'
server {
    listen 80;
    listen [::]:80;
    server_name etiquetas.br;
    root /var/www/etiquetas/public;
    index index.php;
    access_log /var/log/nginx/etiquetas.br.access.log;
    error_log /var/log/nginx/etiquetas.br.error.log;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ /\. { deny all; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    }
}
NGINX
fi
if [[ ! -e /etc/nginx/sites-enabled/etiquetas.br ]]; then
  ln -s /etc/nginx/sites-available/etiquetas.br /etc/nginx/sites-enabled/etiquetas.br
fi
if ! python3 -c 'from pathlib import Path; import sys; sys.exit(0 if any("etiquetas.br" in line.split("#",1)[0].split()[1:] for line in Path("/etc/hosts").read_text().splitlines()) else 1)'; then
  printf '\n127.0.0.1 etiquetas.br # projeto-local\n' >> /etc/hosts
fi
chown -R root:www-data "$target_dir"
find "$target_dir" -type d -exec chmod 755 {} +
find "$target_dir" -type f ! -name config.php -exec chmod 644 {} +
php "$target_dir/bin/provision.php"
nginx -t
systemctl reload nginx
curl --noproxy '*' --fail --silent --show-error --resolve etiquetas.br:80:127.0.0.1 http://etiquetas.br/ -o /dev/null
echo 'Aplicação instalada: http://etiquetas.br'
echo 'Para resolver o domínio no navegador Windows, execute bin/windows-hosts.ps1 no PowerShell como administrador.'
