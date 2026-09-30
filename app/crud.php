<?php
require_once __DIR__ . '/bootstrap.php';

function crud_values(string $entity, array $data): array
{
    if ($entity === 'cliente') {
        $email = text_field($data, 'email', 190, false);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiError('E-mail inválido.');
        }
        return ['nome' => text_field($data, 'nome', 150), 'email' => $email, 'telefone' => text_field($data, 'telefone', 30, false), 'documento' => text_field($data, 'documento', 30, false)];
    }
    if ($entity === 'produto') {
        return ['nome' => text_field($data, 'nome', 150), 'categoria' => text_field($data, 'categoria', 100, false), 'unidade' => text_field($data, 'unidade', 30), 'custo' => number_field($data, 'custo'), 'preco' => number_field($data, 'preco'), 'estoque' => number_field($data, 'estoque', 3)];
    }
    if ($entity === 'despesa') {
        return ['descricao' => text_field($data, 'descricao', 255), 'valor' => number_field($data, 'valor')];
    }
    if ($entity === 'funcionario') {
        $type = text_field($data, 'tipo', 30);
        $status = text_field($data, 'status', 30);
        if (!in_array($type, ['CLT', 'PJ'], true) || !in_array($status, ['Ativo', 'Inativo'], true)) {
            throw new ApiError('Contrato ou status inválido.');
        }
        $extras = $data['extras'] ?? '[]';
        if (is_string($extras)) {
            try {
                $extras = json_decode($extras, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new ApiError('Benefícios inválidos.');
            }
        }
        if (!is_array($extras) || !array_is_list($extras) || count($extras) > 50) {
            throw new ApiError('Benefícios inválidos.');
        }
        foreach ($extras as $extra) {
            text_field(['beneficio' => $extra], 'beneficio', 255, false);
        }
        return ['nome' => text_field($data, 'nome', 150), 'tipo_contrato' => $type, 'documento' => text_field($data, 'doc', 30), 'salario_base' => number_field($data, 'salario'), 'status' => $status, 'vt' => text_field($data, 'vt', 100, false), 'vr' => text_field($data, 'vr', 100, false), 'extras' => json_encode($extras, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
    }
    throw new LogicException('Entidade desconhecida.');
}

function typed_row(array $row): array
{
    foreach (['id', 'produto_id'] as $key) {
        if (isset($row[$key])) $row[$key] = (int) $row[$key];
    }
    foreach (['custo', 'preco', 'estoque', 'valor', 'salario_base', 'qtd', 'valor_total', 'custo_total'] as $key) {
        if (isset($row[$key])) $row[$key] = (float) $row[$key];
    }
    return $row;
}

function list_records(string $table): never
{
    method('GET');
    $id = isset($_GET['id']) ? record_id($_GET['id']) : null;
    $rows = query("SELECT * FROM $table" . ($id ? ' WHERE id = ?' : ' ORDER BY id DESC'), $id ? [$id] : [])->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($id && !$rows) throw new ApiError('Registro não encontrado.', 404);
    respond('ok', '', array_map('typed_row', $rows));
}

function crud(string $entity, string $action): never
{
    $tables = ['cliente' => 'clientes', 'produto' => 'produtos', 'funcionario' => 'funcionarios', 'despesa' => 'despesas'];
    $table = $tables[$entity];
    if ($action === 'get') list_records($table);
    method('POST');
    require_user();
    $id = $action === 'novo' ? null : record_id($_GET['id'] ?? null);
    $values = $action === 'excluir' ? [] : crud_values($entity, request_data());
    $connection = database();
    $connection->begin_transaction();
    try {
        if ($id && !query("SELECT id FROM $table WHERE id = ? FOR UPDATE", [$id])->get_result()->fetch_assoc()) {
            throw new ApiError('Registro não encontrado.', 404);
        }
        if ($action === 'excluir') {
            query("DELETE FROM $table WHERE id = ?", [$id]);
        } elseif ($action === 'alterar') {
            $assignments = implode(', ', array_map(fn ($key) => "$key = ?", array_keys($values)));
            query("UPDATE $table SET $assignments WHERE id = ?", [...array_values($values), $id]);
        } else {
            $columns = implode(', ', array_keys($values));
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            query("INSERT INTO $table ($columns) VALUES ($placeholders)", array_values($values));
        }
        $connection->commit();
    } catch (Throwable $error) {
        $connection->rollback();
        throw $error;
    }
    respond('ok', 'Operação concluída.');
}
