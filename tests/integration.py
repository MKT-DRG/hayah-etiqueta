import http.cookiejar, urllib.request, urllib.parse, urllib.error, re

BASE = 'http://127.0.0.1:8097'
cookies = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(cookies))
def request(path='/', data=None):
    encoded = urllib.parse.urlencode(data, doseq=True).encode() if data is not None else None
    try:
        response = client.open(BASE + path, encoded)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode()
def token(html):
    return re.search(r'name="csrf" value="([^"]+)"', html).group(1)
def check(condition, name):
    assert condition, name
    print('PASS', name)

status, html = request('/?page=print')
check(status == 200 and 'Entrar na plataforma' in html, 'Acesso protegido')
csrf = token(html)
status, html = request('/', {'action':'login','email':'teste@example.com','password':'wrong','csrf':csrf})
check('E-mail ou senha incorretos' in html, 'Senha incorreta rejeitada')
status, html = request('/', {'action':'login','email':'teste@example.com','password':'test-password-123','csrf':csrf})
check('Sua rede começa' in html, 'Login e estado vazio')
csrf = token(html)
payload = {'action':'save','name':'Ana <script>alert(1)</script>', 'company':'Empresa & Cia', 'phone':'(11) 99999-1234','email':'ana@example.com','csrf':csrf}
status, html = request('/', {**payload, 'csrf':'bad'})
check(status == 403, 'CSRF inválido rejeitado')
status, html = request('/', {**payload, 'email':'invalido'})
check('Informe um e-mail válido' in html, 'Validação no servidor')
status, html = request('/', payload)
check('Cadastro salvo com sucesso' in html and 'Ana &lt;script&gt;' in html, 'Cadastro e escape de HTML')
record = re.search(r'page=form&id=(\d+)', html).group(1)
status, html = request('/?q=Empresa')
check('ana@example.com' in html, 'Busca por empresa')
status, html = request('/?page=print', {'csrf':csrf, 'ids[]':[record], 'copies':'15'})
check(html.count('<article class="label">') == 15 and html.count('class="sheet"') == 2 and html.count('data:image/svg+xml;base64,') == 15, '15 etiquetas em duas páginas com QR SVG')
status, html = request('/?page=print', {'csrf':csrf, 'ids[]':[record], 'copies':'999'})
check('no máximo 500 etiquetas' in html, 'Limite de etiquetas')
status, html = request('/', {**payload, 'id':record, 'name':'Ana Atualizada', 'phone':'+351912345678'})
check('Ana Atualizada' in html and 'https://wa.me/351912345678' in html, 'Edição e telefone internacional')
status, html = request('/?page=settings', {'action':'password','csrf':csrf,'current':'test-password-123','password':'new-password-123','confirm':'new-password-123'})
check('Senha atualizada' in html, 'Troca de senha')
status, html = request('/', {'action':'delete','id':record,'csrf':csrf})
check('Cadastro excluído' in html and 'Sua rede começa' in html, 'Exclusão')
status, html = request('/', {'action':'logout','csrf':csrf})
check('Entrar na plataforma' in html, 'Logout')
csrf=token(html)
status, html = request('/', {'action':'login','email':'teste@example.com','password':'new-password-123','csrf':csrf})
check('Empresas e contatos' in html, 'Login com nova senha')
status, html = request('/', {'action':'logout','csrf':token(html)})
csrf=token(html)
for attempt in range(9):
    status, html = request('/', {'action':'login','email':'teste@example.com','password':'wrong','csrf':csrf})
check('Aguarde 15 minutos' in html, 'Limitação de tentativas de login')
print('Todos os testes de integração passaram.')
