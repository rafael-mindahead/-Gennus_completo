docker compose --env-file backend/.env up -d
comando para ligar o servico doccker
docker compose --env-file backend/.env \
  --profile testing up -d db_test
  banco de testes
  