# GameBoxd

Projeto PHP inspirado no Letterboxd, mas voltado para jogos.

## Estado atual

O backend usa PHP com PDO e PostgreSQL no Neon. As correcoes atuais nao criam nem alteram tabelas no banco, porque o schema ja existe no Neon.

Arquivos principais:

- `config/database.php`: cria a conexao PDO.
- `auth/cadastro.php`: recebe cadastro via POST.
- `auth/login.php`: recebe login via POST.
- `auth/logout.php`: encerra a sessao.
- `jogos/listar.php`: lista jogos em JSON.
- `jogos/detalhes.php`: mostra detalhes de um jogo em JSON.
- `jogos/avaliador.php`: grava uma review de jogo para usuario logado.
- `jogos/comentar.php`: comenta uma review.
- `jogos/curtir.php`: curte ou remove curtida de uma review.
- `listas/cria.php`: cria lista de jogos.
- `listas/minha_lista.php`: lista as listas do usuario logado.
- `listas/vizualizador.php`: mostra uma lista e seus jogos.
- `reports/criar.php`: reporta conteudo.
- `perfil/perfil.php`: mostra perfil publico ou do usuario logado.
- `perfil/editar.php`: edita perfil do usuario logado.
- `admin/jogos.php`: gerencia jogos para ADM.
- `admin/tags.php`: gerencia tags para ADM.
- `admin/reportes.php`: modera reportes para ADM.

## Configuracao do Neon

Nao deixe senha do banco direto no codigo.

Opcoes suportadas:

1. Definir a variavel de ambiente `DATABASE_URL`.
2. Criar um arquivo `.env` na raiz baseado em `.env.example`.
3. Criar um arquivo local `config/local.php` baseado em `config/local.example.php`.
4. Definir `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD` e `PGSSLMODE` diretamente no ambiente.

Os arquivos `.env` e `config/local.php` ficam ignorados pelo Git.

Para liberar endpoints de ADM, defina os IDs dos usuarios administradores:

```env
ADMIN_USER_IDS=1,2
```

No XAMPP, confirme se as extensoes PostgreSQL estao habilitadas no `php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
```

Neste computador as DLLs existem em `C:\xampp\php\ext`, mas o comando `php -m` nao mostrou `pdo_pgsql` carregado.

O Neon pode pedir o endpoint ID quando o PHP usa uma libpq antiga sem SNI. A configuracao tenta detectar isso automaticamente pelo host que comeca com `ep-`. Se precisar configurar manualmente, adicione `options=endpoint%3Dep-seu-endpoint` na `DATABASE_URL` ou `PGOPTIONS=endpoint=ep-seu-endpoint`.

## Schema consultado no Neon

Consulta feita em modo somente leitura no schema `public`. O banco tem 11 tabelas:

- `usuarios`: usuarios do sistema.
- `jogos`: catalogo de jogos.
- `reviews`: avaliacoes dos usuarios para jogos.
- `review_likes`: curtidas em reviews.
- `comentarios`: comentarios em reviews.
- `jogos_usuario`: status do jogo para cada usuario.
- `favoritos`: jogos favoritos por usuario e posicao.
- `listas`: listas criadas por usuarios.
- `lista_jogos`: jogos dentro de listas.
- `seguidores`: relacao de seguir usuarios.
- `diario`: registro de tempo/datas jogadas.

Depois da migration `migrations/001_diagram_missing_features.sql`, o projeto tambem usa:

- `tags`: tags gerenciadas por ADM.
- `jogo_tags`: vinculo entre jogos e tags.
- `reports`: conteudos reportados e moderacao.
- `listas.privada`: define se uma lista e privada.

## Tabelas usadas pelo codigo atual

O codigo atual espera que ja existam estas tabelas/colunas:

- `usuarios`: `id`, `username`, `nome`, `email`, `senha_hash`.
- `reviews`: `usuario_id`, `jogo_id`, `nota`, `review`, `spoiler`.

Se os nomes no Neon forem diferentes, ajuste o PHP antes de rodar.

Para reinspecionar o banco sem alterar dados:

```powershell
C:\xampp\php\php.exe -n -d extension_dir=C:\xampp\php\ext -d extension=pdo_pgsql .\scripts\inspect_neon.php
```

## Endpoints de jogos

### Listar jogos

```http
GET /jogos/listar.php
GET /jogos/listar.php?q=zelda
GET /jogos/listar.php?genero=RPG&plataforma=PC&limit=10&offset=0
GET /jogos/listar.php?tag=RPG
```

Resposta:

```json
{
  "data": [],
  "meta": {
    "total": 0,
    "limit": 20,
    "offset": 0
  }
}
```

### Detalhes do jogo

```http
GET /jogos/detalhes.php?id=1
```

Resposta quando existe:

```json
{
  "data": {
    "jogo": {},
    "estatisticas": {
      "total_reviews": 0,
      "media_nota": null
    },
    "reviews_recentes": []
  }
}
```

Resposta quando nao existe:

```json
{
  "erro": "Jogo nao encontrado."
}
```

## Endpoints que exigem login

### Criar lista

```http
POST /listas/cria.php
```

Campos: `nome`, `descricao`, `privada`, `jogos_ids`.

### Minhas listas

```http
GET /listas/minha_lista.php
```

### Visualizar lista

```http
GET /listas/vizualizador.php?id=1
```

### Comentar review

```http
POST /jogos/comentar.php
```

Campos: `review_id`, `comentario`.

### Curtir review

```http
POST /jogos/curtir.php
```

Campos: `review_id`, `acao`. A acao pode ser `toggle`, `curtir` ou `descurtir`.

### Reportar conteudo

```http
POST /reports/criar.php
```

Campos: `tipo_conteudo`, `conteudo_id`, `motivo`. Tipos aceitos: `jogo`, `review`, `comentario`, `lista`, `usuario`.

O report so e criado se o conteudo existir. Listas privadas so podem ser reportadas pelo dono.

### Perfil

```http
GET /perfil/perfil.php
GET /perfil/perfil.php?id=1
GET /perfil/perfil.php?username=usuario
```

Sem `id` ou `username`, retorna o perfil do usuario logado.

### Editar perfil

```http
POST /perfil/editar.php
```

Campos opcionais: `username`, `nome`, `bio`, `avatar`.

Para trocar senha, envie `senha_atual` e `senha_nova`.

## Endpoints de ADM

Todos exigem login e `ADMIN_USER_IDS` configurado.

```http
GET /admin/jogos.php
POST /admin/jogos.php
GET /admin/tags.php
POST /admin/tags.php
GET /admin/reportes.php
POST /admin/reportes.php
```

Observacao: exclusao fisica de jogos e tags nao foi implementada por seguranca. O endpoint de ADM permite criar/editar jogos, criar/editar tags e vincular/desvincular tags de jogos.

Em `admin/jogos.php`, `data_lancamento` deve usar o formato `YYYY-MM-DD`.

# trabs-final
