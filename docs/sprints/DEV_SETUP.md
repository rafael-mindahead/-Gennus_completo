# Executando Gennus 2.0 ao lado do Gennus 1.0

## Proteções
- `docker-compose.yml` **é o ambiente antigo** e continua usando a porta 8080 e volume `gennus_mysql_data`.
- `docker-compose.v2.yml` é um ambiente **novo**: API HTTP em 127.0.0.1:8082 e volume `gennus_v2_mysql_data`.
- O banco é `gennus_v2` vazio; **não é uma cópia nem uma migração do banco antigo**.
- Nunca executar `database/gennus_database.sql` ou `migrate:fresh` em dados reais.

## Iniciar
```sh
git checkout develop/gennus-2
cp backend/.env.example backend/.env
docker compose --env-file backend/.env -f docker-compose.v2.yml build
docker compose --env-file backend/.env -f docker-compose.v2.yml run --rm --no-deps api php artisan key:generate
docker compose --env-file backend/.env -f docker-compose.v2.yml up -d
curl http://127.0.0.1:8082/api/v1/health
```
Esperado: `{"status":"ok","mensagem":"Gennus 2.0 API em desenvolvimento.","data":{"service":"gennus-api","version":"2.0-dev"}}`.

## Parar sem perder dados
```sh
docker compose --env-file backend/.env -f docker-compose.v2.yml down
```
**Não usar `down -v`** se precisar preservar os dados. `backend/.env` contém segredo local e não é commitado.

## Importante
O Laravel ainda não implementa os endpoints antigos `/php/*.php` nem está conectado ao frontend Swift: **somente o health endpoint está planejado para funcionar nesta etapa**.
O Composer inicialmente cria o `composer.lock` durante o build; a próxima validação local deverá versionar esse lockfile para builds reproduzíveis.
