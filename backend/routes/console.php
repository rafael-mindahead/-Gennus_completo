<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Cache\LockTimeoutException;

Artisan::command('inspire', function (){
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Artisan::command('gennus:limpar-backups-produtos', function (){
    $apagados = DB::table('produto_backups_temporarios')->where('expira_em', '<=', now())->delete();
    $this->info("Snapshots expirados removidos: {$apagados}");
})->purpose('Remove snapshots temporarios expirados');
Schedule::command('gennus:limpar-backups-produtos')->everyMinute()->withoutOverLapping();
Artisan::command('gennus:bootstrap-admin', function (){
    if (User::where('perfil', 'administrador')->exists()){
        $this->error('ja existe um administrador cadastrado.');
        return 1;
    }
    $nome = trim((string)$this->ask('Nome do gestor'));
    $email = mb_strtolower(trim((string)$this->ask('E-mail corporativo')));
    $senha = trim((string)$this->secret('Senha do gestor'));
    $confirmacao = trim((string)$this->secret('Confirme a senha'));

    $validator = Validator::make([
        'name' => $nome,
        'email' => $email,
        'password' => $senha,
        'password_confirmation' => $confirmacao,
    ], [
        'name' => ['required', 'string', 'max:120'],
        'email' => [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email'),
        ],
        'password' => [
            'required',
            'string',
            'min:12',
            'max:120',
            'confirmed',
        ],
    ]);
    if ($validator->fails()){
        $this->error($validator->errors()->first());
        return 1;
    }
    if (!$this->confirm('Confirmar criação do primeiro gestor?', false)){
        $this->warn('Operacao cancelada.');
        return 1;
    }
    try {
        $criado = Cache::lock('gennus:bootstrap-admin', 30)->block(5, function () use ($nome, $email, $senha){
            return DB::transaction(function () use ($nome, $email, $senha){
                if (User::where('perfil', 'administrador')->exists()){
                    return false;
                }
                $usuario = User::create([
                    'name' => $nome,
                    'email' => $email,
                    'password' => $senha,
                ]);
                $usuario->forceFill(['perfil' => 'administrador'])->save();
                return true;
            });
        });
    } catch (LockTimeoutException $e){
        $this->error('Outra inicializacao esta em andamento.');
        return 1;
    }
    if (!$criado){
        $this->error('O primeiro gestor ja foi criado ');
        return 1;
    }
    $this->info('primeiro gestor criado com sucesso,');
    return 0;
})->purpose('Provisiona o primeiro administrador do Gennus');
