<?php
 
require_once __DIR__ . '/../modelos/Disciplina.php';
require_once __DIR__ . '/../modelos/Torneo.php';
class SentenciaCategoriaPrueba extends PDOStatement {
    public function __construct(private array $respuesta) {}
    public function execute(?array $params = null): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->respuesta['fila'] ?? false; }
    public function fetchColumn(int $column = 0): mixed { return $this->respuesta['valor'] ?? false; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->respuesta['filas'] ?? []; }
}
class ConexionCategoriaPrueba extends PDO {
    public array $sql = [];
    public bool $transaccion = false;
    public bool $revertida = false;
    public function __construct(private array $respuestas) {}
    public function prepare(string $query, array $options = []): PDOStatement|false {
        $this->sql[] = $query;
        if (!$this->respuestas) throw new RuntimeException('Consulta inesperada: ' . $query);
        return new SentenciaCategoriaPrueba(array_shift($this->respuestas));
    }
    public function beginTransaction(): bool { $this->transaccion = true; return true; }
    public function commit(): bool { $this->transaccion = false; return true; }
    public function rollBack(): bool { $this->transaccion = false; $this->revertida = true; return true; }
    public function inTransaction(): bool { return $this->transaccion; }
    public function lastInsertId(?string $name = null): string|false { return '42'; }
}
function comprobarCategoria(bool $valor, string $mensaje): void { if (!$valor) throw new RuntimeException($mensaje); }
function esperarRechazoCategoria(callable $operacion): void {
    try { $operacion(); } catch (InvalidArgumentException|DomainException $e) { return; }
    throw new RuntimeException('Se esperaba un rechazo.');
}
foreach (['', 'a', str_repeat('á', 51)] as $nombre) {
    esperarRechazoCategoria(fn() => (new Disciplina(new ConexionCategoriaPrueba([])))->crearCategoria($nombre));
}
$db = new ConexionCategoriaPrueba([[]]);
$creada = (new Disciplina($db))->crearCategoria('  Mixta  ');
comprobarCategoria($creada === ['id_categoria'=>42,'nombre'=>'Mixta'], 'Creación y normalización');
$db = new ConexionCategoriaPrueba([['valor'=>'Anterior'], []]);
$editada = (new Disciplina($db))->actualizarCategoria(42, 'Nueva');
comprobarCategoria($editada['nombre_anterior'] === 'Anterior' && !$db->inTransaction(), 'Edición conserva detalle para auditoría');
$db = new ConexionCategoriaPrueba([['valor'=>false]]);
esperarRechazoCategoria(fn() => (new Disciplina($db))->actualizarCategoria(42, 'Nueva'));
comprobarCategoria($db->revertida, 'Edición inexistente revierte');
foreach ([['torneos'=>2,'disciplinas'=>[]], ['torneos'=>0,'disciplinas'=>['Fútbol']]] as $uso) {
    $respuestas = [['fila'=>['id_categoria'=>42,'nombre'=>'Libre']], ['valor'=>$uso['torneos']]];
    if (!$uso['torneos']) $respuestas[] = ['filas'=>$uso['disciplinas']];
    $db = new ConexionCategoriaPrueba($respuestas);
    esperarRechazoCategoria(fn() => (new Disciplina($db))->eliminarCategoria(42));
    comprobarCategoria($db->revertida && !preg_grep('/DELETE FROM categorias/', $db->sql), 'Uso bloqueado antes de borrar');
}
$db = new ConexionCategoriaPrueba([['fila'=>['id_categoria'=>42,'nombre'=>'Temporal']], ['valor'=>0], ['filas'=>[]], []]);
$eliminada = (new Disciplina($db))->eliminarCategoria(42);
comprobarCategoria($eliminada['id_categoria'] === 42 && !$db->inTransaction(), 'Elimina únicamente sin uso');
$db = new ConexionCategoriaPrueba([['fila'=>['configuradas'=>0,'coincide'=>0]], ['fila'=>['configurados'=>0,'coincide'=>0]]]);
comprobarCategoria((new Torneo($db))->combinacionPermitida(1,42,1), 'Sin asociaciones configuradas se usan los catálogos disponibles');
$db = new ConexionCategoriaPrueba([['fila'=>['configuradas'=>1,'coincide'=>0]]]);
comprobarCategoria(!(new Torneo($db))->combinacionPermitida(1,42,1), 'Una asociación explícita incompatible se rechaza');
$db = new ConexionCategoriaPrueba([['fila'=>['configuradas'=>1,'coincide'=>1]], ['fila'=>['configurados'=>1,'coincide'=>1]]]);
comprobarCategoria((new Torneo($db))->combinacionPermitida(1,42,1), 'Combinación asociada permitida');
echo "12 casos de Categorías y asociaciones correctos; sin acceso a la base de datos.\n";
