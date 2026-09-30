<?php
require_once __DIR__ . '/crud.php';

function sale(string $action): never
{
    method('POST');
    require_user();
    $id = $action === 'novo' ? null : record_id($_GET['id'] ?? null);
    $data = $action === 'excluir' ? [] : request_data();
    $quantity = $action === 'excluir' ? 0 : number_field($data, 'qtd', 3, true);
    $connection = database();
    $connection->begin_transaction();
    try {
        $old = $id ? query('SELECT * FROM vendas WHERE id = ? FOR UPDATE', [$id])->get_result()->fetch_assoc() : null;
        if ($id && !$old) throw new ApiError('Venda não encontrada.', 404);
        $productId = $old ? (int) $old['produto_id'] : record_id($data['produto_id'] ?? null);
        $product = query('SELECT * FROM produtos WHERE id = ? FOR UPDATE', [$productId])->get_result()->fetch_assoc();
        if (!$old && !$product) throw new ApiError('Produto não encontrado.', 404);
        $oldQuantity = $old ? (float) $old['qtd'] : 0;
        $difference = round($quantity - $oldQuantity, 3);
        if ($difference > 0 && (!$product || $difference > (float) $product['estoque'] + 0.0000001)) {
            throw new ApiError('Estoque insuficiente.', 409);
        }
        if ($product) {
            $stock = number_field(['estoque' => round((float) $product['estoque'] - $difference, 3)], 'estoque', 3);
            query('UPDATE produtos SET estoque = ? WHERE id = ?', [$stock, $productId]);
        }
        if ($action === 'excluir') {
            query('DELETE FROM vendas WHERE id = ?', [$id]);
        } else {
            // Alterações usam a fotografia histórica; novas vendas usam os preços do banco.
            $price = $old ? (float) $old['valor_total'] / $oldQuantity : (float) $product['preco'];
            $cost = $old ? (float) $old['custo_total'] / $oldQuantity : (float) $product['custo'];
            $total = number_field(['valor_total' => round($price * $quantity, 2)], 'valor_total');
            $totalCost = number_field(['custo_total' => round($cost * $quantity, 2)], 'custo_total');
            if ($old) {
                query('UPDATE vendas SET qtd = ?, valor_total = ?, custo_total = ? WHERE id = ?', [$quantity, $total, $totalCost, $id]);
            } else {
                query('INSERT INTO vendas (produto_id, produto_nome, qtd, unidade, valor_total, custo_total) VALUES (?, ?, ?, ?, ?, ?)', [$productId, $product['nome'], $quantity, $product['unidade'], $total, $totalCost]);
            }
        }
        $connection->commit();
    } catch (Throwable $error) {
        $connection->rollback();
        throw $error;
    }
    respond('ok', 'Operação concluída.');
}
