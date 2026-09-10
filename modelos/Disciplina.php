<?php

class Disciplina
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodas(): array
    {
        $consulta = $this->conexion->prepare(
            "SELECT
                d.id_disciplina,
                d.nombre,
                d.estado,
                (SELECT COUNT(*) FROM torneos t WHERE t.id_disciplina = d.id_disciplina) AS cantidad_torneos,
                (SELECT COUNT(*) FROM disciplina_categoria dc WHERE dc.id_disciplina = d.id_disciplina) AS cantidad_categorias,
                (SELECT COUNT(*) FROM disciplina_tipo_torneo dt WHERE dt.id_disciplina = d.id_disciplina) AS cantidad_tipos,
                (SELECT GROUP_CONCAT(dc.id_categoria ORDER BY dc.id_categoria SEPARATOR ',') FROM disciplina_categoria dc WHERE dc.id_disciplina = d.id_disciplina) AS categorias_ids,
                (SELECT GROUP_CONCAT(dt.id_tipo_torneo ORDER BY dt.id_tipo_torneo SEPARATOR ',') FROM disciplina_tipo_torneo dt WHERE dt.id_disciplina = d.id_disciplina) AS tipos_ids
             FROM disciplinas d
             ORDER BY d.estado = 'activa' DESC, d.nombre ASC"
        );
        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $fila['categorias_ids'] = $this->cadenaIdsAEnteros($fila['categorias_ids'] ?? null);
            $fila['tipos_ids'] = $this->cadenaIdsAEnteros($fila['tipos_ids'] ?? null);
        }
        unset($fila);
        return $filas;
    }

    public function obtenerCatalogosAsociacion(): array
    {
        $categorias = $this->conexion->query(
            "SELECT
                c.id_categoria AS id,
                c.nombre,
                (SELECT COUNT(*) FROM torneos t WHERE t.id_categoria = c.id_categoria) AS cantidad_torneos,
                (SELECT COUNT(*) FROM disciplina_categoria dc WHERE dc.id_categoria = c.id_categoria) AS cantidad_disciplinas
             FROM categorias c
             ORDER BY c.nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        $tipos = $this->conexion->query("SELECT id_tipo_torneo AS id, nombre FROM tipos_torneo ORDER BY nombre ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
        $asociaciones = $this->conexion->query(
            'SELECT dc.id_categoria, d.id_disciplina AS id, d.nombre, d.estado
             FROM disciplina_categoria dc JOIN disciplinas d ON d.id_disciplina = dc.id_disciplina
             ORDER BY d.nombre'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categorias as &$categoria) {
            $categoria['disciplinas'] = array_values(array_filter($asociaciones,
                static fn(array $fila): bool => (int) $fila['id_categoria'] === (int) $categoria['id']));
            $categoria['puede_eliminar'] = (int) $categoria['cantidad_torneos'] === 0 && (int) $categoria['cantidad_disciplinas'] === 0;
        }
        unset($categoria);
        return ['categorias' => $categorias, 'tipos' => $tipos];
    }

    private function validarNombreCategoria(string $nombre): string
    {
        $nombre = trim($nombre);
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 50) {
            throw new InvalidArgumentException('El nombre debe tener entre 2 y 50 caracteres.');
        }
        return $nombre;
    }

    public function crearCategoria(string $nombre): array
    {
        $nombre = $this->validarNombreCategoria($nombre);
        $consulta = $this->conexion->prepare('INSERT INTO categorias (nombre) VALUES (?)');
        $consulta->execute([$nombre]);
        return ['id_categoria' => (int) $this->conexion->lastInsertId(), 'nombre' => $nombre];
    }

    public function actualizarCategoria(int $idCategoria, string $nombre): array
    {
        $nombre = $this->validarNombreCategoria($nombre);
        if ($idCategoria <= 0) throw new InvalidArgumentException('Categoría no válida.');
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare('SELECT nombre FROM categorias WHERE id_categoria = ? FOR UPDATE');
            $consulta->execute([$idCategoria]);
            $anterior = $consulta->fetchColumn();
            if ($anterior === false) throw new DomainException('La categoría ya no existe.');
            $consulta = $this->conexion->prepare('UPDATE categorias SET nombre = ? WHERE id_categoria = ?');
            $consulta->execute([$nombre, $idCategoria]);
            $this->conexion->commit();
            return ['id_categoria' => $idCategoria, 'nombre' => $nombre, 'nombre_anterior' => $anterior];
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $error;
        }
    }

    public function eliminarCategoria(int $idCategoria): array
    {
        if ($idCategoria <= 0) {
            throw new InvalidArgumentException('Categoría no válida.');
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                "SELECT id_categoria, nombre
                 FROM categorias
                 WHERE id_categoria = :id_categoria
                 FOR UPDATE"
            );
            $consulta->execute([':id_categoria' => $idCategoria]);
            $categoria = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$categoria) {
                throw new DomainException('La categoría ya no existe.');
            }

            $consultaTorneos = $this->conexion->prepare(
                "SELECT COUNT(*)
                 FROM torneos
                 WHERE id_categoria = :id_categoria"
            );
            $consultaTorneos->execute([':id_categoria' => $idCategoria]);
            $cantidadTorneos = (int) $consultaTorneos->fetchColumn();
            if ($cantidadTorneos > 0) {
                throw new DomainException(
                    'No se puede eliminar la categoría porque está siendo utilizada por ' .
                    $cantidadTorneos . ($cantidadTorneos === 1 ? ' torneo.' : ' torneos.') .
                    ' Para conservar el historial, utiliza otra categoría en los torneos nuevos.'
                );
            }

            $consultaDisciplinas = $this->conexion->prepare(
                "SELECT d.nombre
                 FROM disciplinas d
                 INNER JOIN disciplina_categoria dc ON dc.id_disciplina = d.id_disciplina
                 WHERE dc.id_categoria = :id_categoria
                 ORDER BY d.nombre ASC"
            );
            $consultaDisciplinas->execute([':id_categoria' => $idCategoria]);
            $sinAlternativa = $consultaDisciplinas->fetchAll(PDO::FETCH_COLUMN);
            if ($sinAlternativa) {
                $nombres = implode(', ', array_slice(array_map('strval', $sinAlternativa), 0, 3));
                if (count($sinAlternativa) > 3) {
                    $nombres .= ' y ' . (count($sinAlternativa) - 3) . ' más';
                }
                throw new DomainException(
                    'No se puede eliminar: la categoría está asociada a ' . $nombres .
                    '. Revisa sus asociaciones desde Disciplinas; no se eliminan automáticamente.'
                );
            }

            $eliminar = $this->conexion->prepare("DELETE FROM categorias WHERE id_categoria = :id_categoria");
            $eliminar->execute([':id_categoria' => $idCategoria]);
            $this->conexion->commit();

            return [
                'id_categoria' => $idCategoria,
                'nombre' => (string) $categoria['nombre']
            ];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    public function eliminarDisciplina(int $idDisciplina): array
    {
        if ($idDisciplina <= 0) {
            throw new InvalidArgumentException('Disciplina no válida.');
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                "SELECT id_disciplina, nombre
                 FROM disciplinas
                 WHERE id_disciplina = :id_disciplina
                 FOR UPDATE"
            );
            $consulta->execute([':id_disciplina' => $idDisciplina]);
            $disciplina = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$disciplina) {
                throw new DomainException('La disciplina ya no existe.');
            }

            $consultaTorneos = $this->conexion->prepare(
                "SELECT COUNT(*)
                 FROM torneos
                 WHERE id_disciplina = :id_disciplina"
            );
            $consultaTorneos->execute([':id_disciplina' => $idDisciplina]);
            $cantidadTorneos = (int) $consultaTorneos->fetchColumn();
            if ($cantidadTorneos > 0) {
                throw new DomainException(
                    'No se puede eliminar la disciplina porque está siendo utilizada por ' .
                    $cantidadTorneos . ($cantidadTorneos === 1 ? ' torneo.' : ' torneos.') .
                    ' Para conservar el historial, cambia su estado a Inactiva.'
                );
            }

            $eliminar = $this->conexion->prepare(
                "DELETE FROM disciplinas WHERE id_disciplina = :id_disciplina"
            );
            $eliminar->execute([':id_disciplina' => $idDisciplina]);
            $this->conexion->commit();

            return [
                'id_disciplina' => $idDisciplina,
                'nombre' => (string) $disciplina['nombre']
            ];
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
    }

    public function obtenerPorId(int $idDisciplina): array|false
    {
        $consulta = $this->conexion->prepare(
            "SELECT id_disciplina, nombre, estado
             FROM disciplinas
             WHERE id_disciplina = :id_disciplina
             LIMIT 1"
        );
        $consulta->execute([':id_disciplina' => $idDisciplina]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$fila) return false;

        $fila['categorias_ids'] = $this->obtenerIdsRelacion('disciplina_categoria', 'id_categoria', $idDisciplina);
        $fila['tipos_ids'] = $this->obtenerIdsRelacion('disciplina_tipo_torneo', 'id_tipo_torneo', $idDisciplina);
        return $fila;
    }

    public function existeNombre(string $nombre, ?int $exceptoId = null): bool
    {
        $sql = "SELECT 1 FROM disciplinas WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))";
        $parametros = [':nombre' => $nombre];
        if ($exceptoId !== null) {
            $sql .= " AND id_disciplina <> :excepto_id";
            $parametros[':excepto_id'] = $exceptoId;
        }
        $sql .= " LIMIT 1";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return (bool) $consulta->fetchColumn();
    }

    public function crear(string $nombre, string $estado, array $categorias, array $tipos): int
    {
        $this->validarAsociaciones($categorias, $tipos);
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                "INSERT INTO disciplinas (nombre, estado) VALUES (:nombre, :estado)"
            );
            $consulta->execute([':nombre' => $nombre, ':estado' => $estado]);
            $id = (int) $this->conexion->lastInsertId();
            $this->reemplazarAsociaciones($id, $categorias, $tipos);
            $this->conexion->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $idDisciplina, string $nombre, string $estado, array $categorias, array $tipos): bool
    {
        $this->validarAsociaciones($categorias, $tipos);
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                "UPDATE disciplinas SET nombre = :nombre, estado = :estado WHERE id_disciplina = :id_disciplina"
            );
            $ok = $consulta->execute([
                ':nombre' => $nombre,
                ':estado' => $estado,
                ':id_disciplina' => $idDisciplina
            ]);
            $this->reemplazarAsociaciones($idDisciplina, $categorias, $tipos);
            $this->conexion->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $e;
        }
    }

    private function validarAsociaciones(array &$categorias, array &$tipos): void
    {
        $categorias = array_values(array_unique(array_filter(array_map('intval', $categorias), static fn(int $id): bool => $id > 0)));
        $tipos = array_values(array_unique(array_filter(array_map('intval', $tipos), static fn(int $id): bool => $id > 0)));
        if (!$categorias) throw new InvalidArgumentException('Selecciona al menos una categoría para la disciplina.');
        if (!$tipos) throw new InvalidArgumentException('Selecciona al menos un tipo de torneo para la disciplina.');

        $this->validarIdsCatalogo('categorias', 'id_categoria', $categorias, 'categoría');
        $this->validarIdsCatalogo('tipos_torneo', 'id_tipo_torneo', $tipos, 'tipo de torneo');
    }

    private function validarIdsCatalogo(string $tabla, string $columna, array $ids, string $etiqueta): void
    {
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $q = $this->conexion->prepare("SELECT COUNT(*) FROM {$tabla} WHERE {$columna} IN ({$marcadores})");
        $q->execute($ids);
        if ((int) $q->fetchColumn() !== count($ids)) {
            throw new InvalidArgumentException('Hay una ' . $etiqueta . ' seleccionada que no existe.');
        }
    }

    private function reemplazarAsociaciones(int $idDisciplina, array $categorias, array $tipos): void
    {
        $this->conexion->prepare("DELETE FROM disciplina_categoria WHERE id_disciplina = ?")->execute([$idDisciplina]);
        $this->conexion->prepare("DELETE FROM disciplina_tipo_torneo WHERE id_disciplina = ?")->execute([$idDisciplina]);

        $qCat = $this->conexion->prepare("INSERT INTO disciplina_categoria (id_disciplina, id_categoria) VALUES (?, ?)");
        foreach ($categorias as $id) $qCat->execute([$idDisciplina, $id]);

        $qTipo = $this->conexion->prepare("INSERT INTO disciplina_tipo_torneo (id_disciplina, id_tipo_torneo) VALUES (?, ?)");
        foreach ($tipos as $id) $qTipo->execute([$idDisciplina, $id]);
    }

    private function obtenerIdsRelacion(string $tabla, string $columna, int $idDisciplina): array
    {
        $q = $this->conexion->prepare("SELECT {$columna} FROM {$tabla} WHERE id_disciplina = ? ORDER BY {$columna}");
        $q->execute([$idDisciplina]);
        return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    }

    private function cadenaIdsAEnteros(?string $valor): array
    {
        if ($valor === null || trim($valor) === '') return [];
        return array_map('intval', explode(',', $valor));
    }
}
