# Publicação na hospedagem (cPanel/Hostinger) e backup

Endereço de produção: **https://vulpesdigital.com.br/atelie** (sistema numa subpasta de um domínio que já tem outro site).

## Gerar o pacote

No computador de desenvolvimento (precisa de Node, PHP, openssl e rsync):

```bash
./empacotar.sh            # subpasta padrão: atelie
```

Saída em `deploy/` (a pasta não vai para o Git):

| Arquivo | Para quê |
|---|---|
| `atelie-hostinger.zip` | Conteúdo que vai em `public_html/atelie/` (front já compilado com base `/atelie/`, API e `.htaccess`) |
| `atelie-banco.sql` | Tabelas + usuário `admin` com **senha temporária forte** (gerada a cada execução) + 6 serviços de exemplo |
| `config-atelie.php` | Modelo de configuração de produção, vai **fora** do `public_html` |
| `SENHA-TEMPORARIA.txt` | Senha do primeiro login (guarde e apague depois da troca) |

> Cada execução de `./empacotar.sh` gera uma senha nova. Use o `.sql` e o `.txt` **da mesma execução**.

## Estrutura final no servidor

```
/home/USUARIO/
├── config/atelie.php          ← configuração (senha do banco). FORA do site
├── logs/                      ← log de erros da API (criado automaticamente). FORA do site
└── public_html/
    ├── (arquivos do outro site)
    └── atelie/                ← conteúdo do atelie-hostinger.zip
        ├── .htaccess, index.html, assets/, logo.jpg
        └── api/
```

## Passo a passo

1. **Banco** — cPanel → *MySQL® Databases*: crie um banco (ex.: `usuario_atelie`), um usuário com senha forte e adicione o usuário ao banco com *todos os privilégios*. Anote os nomes completos (com prefixo).
2. **Importar** — phpMyAdmin → selecione o banco → *Importar* → `atelie-banco.sql`. Devem aparecer 7 tabelas.
3. **Configuração** — copie `config-atelie.php` para `/home/USUARIO/config/atelie.php` (crie a pasta `config` ao lado de `public_html`, não dentro) e troque `nome`, `usuario` e `senha` do banco.
   O cookie de sessão já fica restrito a `/atelie` (não conflita com o outro site do domínio).
4. **Arquivos** — Gerenciador de Arquivos → crie `public_html/atelie`, envie `atelie-hostinger.zip` e extraia lá dentro. Confirme que o `.htaccess` apareceu (ative "mostrar arquivos ocultos").
5. **PHP** — *Selecionar versão do PHP* para o domínio: **8.2 ou superior** (o sistema foi testado em 8.5), com a extensão `pdo_mysql` ativa.
6. **Primeiro acesso** — abra https://vulpesdigital.com.br/atelie, entre com `admin` e a senha de `SENHA-TEMPORARIA.txt`. O sistema obriga a trocar a senha. Depois, apague o `.txt`.
7. **Conferir** (ver lista abaixo).

## Conferência pós-publicação

- [ ] `https://vulpesdigital.com.br/atelie` abre o login com a logo.
- [ ] `http://...` redireciona para `https://...`.
- [ ] Atualizar (F5) em `/atelie/os` não dá 404.
- [ ] `https://vulpesdigital.com.br/atelie/api/me` responde JSON `nao_autenticado` (401).
- [ ] `https://vulpesdigital.com.br/atelie/api/app/Core/Config.php` e `/atelie/api/config/config.example.php` dão **403** (código-fonte não pode ser servido).
- [ ] O outro site do domínio continua funcionando normalmente.
- [ ] Cadastrar um cliente, criar uma OS e conferir o total.

## Particularidades do hPanel da Hostinger (aprendidas na publicação)

* O caminho real é `/domains/SEU-DOMINIO/public_html`; a pasta `config` vai em `/domains/SEU-DOMINIO/config` (um nível acima).
* **Extrair o zip com "Aqui" cria uma subpasta** com o nome do zip. Depois, selecione os itens dentro dela e use *Mudar para…* → `public_html/atelie`, e apague a subpasta vazia.
* **Apague o `.zip` do servidor** depois de extrair: ele ficaria baixável pelo site.
* No editor de código, a alteração do `config/atelie.php` só vale **depois de clicar em "Implantar"** (botão no canto superior direito).
* O banco criado pelo hPanel tem prefixo (`usuario_...`) no nome e no usuário; o phpMyAdmin abre pelo botão do banco e a importação usa a aba *Importar*.
* O Chrome pode preencher o login do sistema com credenciais salvas de **outro site do mesmo domínio**: confira o usuário (`admin`) antes de entrar.

## Se algo der errado

| Sintoma | Causa provável |
|---|---|
| 500 em `/atelie/api/...` | `config/atelie.php` não encontrado ou com dados do banco errados; veja `/home/USUARIO/logs/atelie-api.log` |
| 404 ao atualizar uma tela | `.htaccess` não foi extraído (arquivo oculto) ou `AllowOverride` desligado: peça ao suporte |
| Tela em branco | O front foi gerado sem a base `/atelie/`; gere de novo com `./empacotar.sh` |
| Login sempre volta para a tela de entrada | Cookie `Secure` sem HTTPS, ou `cookie_path` diferente da subpasta |
| Erro de "CHECK" ao importar o SQL | MySQL antigo (< 8.0.16) ou MariaDB < 10.2: peça ao suporte para atualizar |

## Atualizações futuras

Rode `./empacotar.sh` de novo e envie **somente** o conteúdo do novo `.zip` por cima de `public_html/atelie/`
(não precisa reimportar o banco nem mexer no `config/atelie.php`). Se houver mudança de tabelas, ela virá
acompanhada de um script de migração.

## Backup

* Ative o backup automático da hospedagem.
* Exportação semanal: `php api/bin/backup.php /home/USUARIO/backups` (agende em *Cron Jobs* do cPanel; ajuste o caminho do `php` e do script) ou use *Exportar* no phpMyAdmin.
* **Restaurar**: phpMyAdmin → Importar → arquivo `.sql`; ou `mysql -u USUARIO -p BANCO < backup.sql`. Teste uma restauração ao menos uma vez.

## Desenvolvimento x produção

Em desenvolvimento (`./dev.sh`) o front roda na raiz (`/`) com proxy para a API; o `admin/1234` do `database/seed.sql`
**só existe em desenvolvimento**. Em produção use sempre o `atelie-banco.sql` gerado pelo `empacotar.sh`.
