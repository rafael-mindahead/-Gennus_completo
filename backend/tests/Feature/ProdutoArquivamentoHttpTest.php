<?php
namespace Tests\Feature;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ProdutoArquivamentoHttpTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $perfil): User
    {
        $usuario = User::create([
            'name' => ucfirst($perfil) . ' Teste',
            'email' => $perfil . '@gennus.test',
            'password' => 'SenhaSegura123',
        ]);

        $usuario->forceFill(['perfil' => $perfil])->save();

        return $usuario->fresh();
    }

    private function produto(): Produto
    {
        return Produto::create([
            'nome' => 'Mouse Gamer',
            'categoria' => 'Perifericos',
            'unidade' => 'un',
            'custo' => '50.00',
            'preco' => '99.90',
            'estoque' => '10.000',
            'codigo_barras' => '7900000000001',
        ]);
    }

    public function test_operador_arquiva_com_snapshot_e_auditoria(): void
    {
        $operador = $this->usuario('operador');
        $produto = $this->produto();

        $this->actingAs($operador);

        $this->postJson(
            "/php/produto_excluir.php?id={$produto->id}"
        )->assertOk();

        $this->assertSoftDeleted('produtos', [
            'id' => $produto->id,
        ]);

        $this->assertDatabaseHas('produto_backups_temporarios', [
            'produto_id' => $produto->id,
            'operador_id' => $operador->id,
            'operador_nome' => 'Operador Teste',
        ]);

        $this->assertDatabaseHas('produto_auditorias', [
            'produto_id' => $produto->id,
            'acao' => 'arquivado',
        ]);

        $backup = DB::table('produto_backups_temporarios')
            ->where('produto_id', $produto->id)
            ->first();

        $dados = json_decode($backup->dados, true);

        $this->assertSame('Mouse Gamer', $dados['nome']);
        $this->assertSame('7900000000001', $dados['codigo_barras']);
    }

    public function test_operador_nao_pode_restaurar(): void
    {
        $produto = $this->produto();

        $this->actingAs($this->usuario('operador'));

        $this->postJson(
            "/php/produto_excluir.php?id={$produto->id}"
        )->assertOk();

        $this->postJson(
            "/php/produto_restaurar.php?id={$produto->id}"
        )->assertForbidden();

        $this->assertSoftDeleted('produtos', [
            'id' => $produto->id,
        ]);
    }

    public function test_administrador_restaura_antes_de_48_horas(): void
    {
        $produto = $this->produto();

        $this->actingAs($this->usuario('operador'));

        $this->postJson(
            "/php/produto_excluir.php?id={$produto->id}"
        )->assertOk();

        $this->travel(47)->hours();

        $this->actingAs($this->usuario('administrador'));

        $this->postJson(
            "/php/produto_restaurar.php?id={$produto->id}"
        )->assertOk();

        $this->assertNotNull(Produto::find($produto->id));

        $this->assertDatabaseMissing('produto_backups_temporarios', [
            'produto_id' => $produto->id,
        ]);

        $this->assertDatabaseHas('produto_auditorias', [
            'produto_id' => $produto->id,
            'acao' => 'restaurado',
        ]);
    }

    public function test_backup_expira_apos_48_horas(): void
    {
        $produto = $this->produto();

        $this->actingAs($this->usuario('operador'));

        $this->postJson(
            "/php/produto_excluir.php?id={$produto->id}"
        )->assertOk();

        $this->travel(48)->hours();

        $this->actingAs($this->usuario('administrador'));

        $this->postJson(
            "/php/produto_restaurar.php?id={$produto->id}"
        )->assertStatus(410);

        $this->artisan('gennus:limpar-backups-produtos')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('produto_backups_temporarios', [
            'produto_id' => $produto->id,
        ]);

        $this->assertDatabaseHas('produto_auditorias', [
            'produto_id' => $produto->id,
            'acao' => 'arquivado',
        ]);

        $this->assertSoftDeleted('produtos', [
            'id' => $produto->id,
        ]);
    }

    public function test_leitor_nao_pode_arquivar(): void
    {
        $produto = $this->produto();

        $this->actingAs($this->usuario('leitor'));

        $this->postJson(
            "/php/produto_excluir.php?id={$produto->id}"
        )->assertForbidden();

        $this->assertNotNull(Produto::find($produto->id));
    }
}
