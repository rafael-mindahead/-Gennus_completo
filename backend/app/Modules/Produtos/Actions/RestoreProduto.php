<?php
namespace App\Modules\Produtos\Actions;
use App\Models\Produto;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RestoreProduto
{
    public function execute(int $id, User $administrador): Produto
    {
        Gate::forUser($administrador)->authorize('produtos.restaurar');

        return DB::transaction(function () use ($id, $administrador) {
            $produto = Produto::withTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
            if (!$produto->trashed()) {
                throw ValidationException::withMessages([
                    'produto' => 'O produto não está arquivado.',
                ]);
            }
            $backup = DB::table('produto_backups_temporarios')
                ->where('produto_id', $id)
                ->lockForUpdate()
                ->first();
            if ($backup === null || Carbon::parse($backup->expira_em)->lessThanOrEqualTo(now())) {
                throw new DomainException('Prazo de recuperação de 48 horas encerrado.');
            }
            $produto->restore();

            DB::table('produto_backups_temporarios')->where('id', $backup->id)->delete();
            DB::table('produto_auditorias')->insert([
                'produto_id' => $produto->id,
                'usuario_id' => $administrador->id,
                'usuario_nome' => $administrador->name,
                'produto_nome' => $produto->nome,
                'acao' => 'restaurado',
                'ocorrido_em' => now(),
            ]);

            return $produto->fresh();
        }, 3);
    }
}