# Backend Laravel 13 — Gennus 2.0
Este backend é **paralelo** ao PHP procedimental do Gennus 1.0; nenhuma rota antiga foi substituída.
Health: `GET /api/v1/health`; health do Laravel: `GET /up`.

Pré-requisitos: PHP >=8.3 e Composer 2 (ou Docker, configurado separadamente).
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan test
```
A aplicação **não contém CRUD nem migrations do legado**. Os testes HTTP atuais não escrevem no banco.
O banco `gennus_v2` deve ser isolado: jamais importar o SQL de desenvolvimento original em um banco com dados reais.
A ausência de `composer.lock` será resolvida após primeira instalação validada e o lockfile será versionado.
