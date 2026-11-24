<?php
namespace App\Modules\Produtos\Actions;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
final class DeleteProduto
{
    public function execute(int $id, User $operador): void
    {
        Gate::forUser($operador)->authorize('produtos.excluir');

        DB::transaction(function () use ($id, $operador): void {
            $produto = Produto::query()->lockForUpdate()->findOrFail($id);
            $agora = now();

            DB::table('produto_backups_temporarios')->insert([
                'produto_id' => $produto->id,
                'operador_id' => $operador->id,
                'operador_nome' => $operador->name,
                'produto_nome' => $produto->nome,
                'dados' => json_encode($produto->only([
                    'id',
                    'nome',
                    'categoria',
                    'unidade',
                    'custo',
                    'preco',
                    'estoque', 
                    'codigo_barras',
                ]),
                JSON_THROW_ON_ERROR
                ),
                'criado_em' => $agora,
                'expira_em' => $agora->copy()->addHours(48),
            ]);
            DB::table('produto_auditorias')->insert([
                'produto_id' => $produto->id,
                'usuario_id' => $operador->id,
                'usuario_nome' => $operador->name,
                'produto_nome' => $produto->nome,
                'acao' => 'arquivado',
                'ocorrido_em' => $agora,
            ]);
            $produto->delete();
        }, 3);   
    }
}