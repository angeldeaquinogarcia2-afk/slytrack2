<?php
// ============================================================
//  api/index.php  — Endpoint único de la API REST
//  Rutas:
//    POST   /api/?action=login          Verificar token
//    GET    /api/?action=bienes         Listar bienes del área
//    POST   /api/?action=crear          Crear bien (multipart)
//    POST   /api/?action=actualizar     Actualizar bien (multipart)
//    POST   /api/?action=eliminar       Eliminar bien
//    GET    /api/?action=exportar       Datos para exportar a Excel
// ============================================================

require_once __DIR__ . '/config.php';
cors();

$action = $_GET['action'] ?? '';

match ($action) {
    'login'       => actionLogin(),
    'bienes'      => actionBienes(),
    'crear'       => actionCrear(),
    'actualizar'  => actionActualizar(),
    'eliminar'    => actionEliminar(),
    'exportar'    => actionExportar(),
    default       => jsonResponse(['ok' => false, 'error' => 'Acción no válida'], 400),
};

// ── LOGIN ────────────────────────────────────────────────────
function actionLogin(): void {
    $body  = json_decode(file_get_contents('php://input'), true);
    $token = trim($body['token'] ?? '');

    if (!$token) {
        jsonResponse(['ok' => false, 'error' => 'Token requerido'], 400);
    }

    $stmt = getDB()->prepare('SELECT id, nombre FROM areas WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $area = $stmt->fetch();

    if (!$area) {
        jsonResponse(['ok' => false, 'error' => 'Token incorrecto'], 401);
    }

    jsonResponse(['ok' => true, 'area_id' => $area['id'], 'area_nombre' => $area['nombre']]);
}

// ── LISTAR BIENES ────────────────────────────────────────────
function actionBienes(): void {
    $area_id = intval($_GET['area_id'] ?? 0);
    $search  = trim($_GET['search'] ?? '');
    $estado  = trim($_GET['estado'] ?? '');

    if (!$area_id) jsonResponse(['ok' => false, 'error' => 'area_id requerido'], 400);

    $where  = ['b.area_id = ?'];
    $params = [$area_id];

    if ($search) {
        $where[]  = '(b.no_etiqueta LIKE ? OR b.marca LIKE ? OR b.modelo LIKE ? OR b.caracteristicas LIKE ?)';
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like, $like, $like]);
    }
    if ($estado) {
        $where[]  = 'b.estado_uso = ?';
        $params[] = $estado;
    }

    $sql  = 'SELECT b.*, a.nombre AS area_nombre
             FROM bienes b
             JOIN areas a ON a.id = b.area_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY b.creado_en DESC';

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $bienes = $stmt->fetchAll();

    // Estadísticas del área
    $stmtStats = getDB()->prepare(
        'SELECT
            COUNT(*) AS total,
            SUM(estado_uso = "Bueno")   AS buenos,
            SUM(estado_uso = "Regular") AS regulares,
            SUM(estado_uso = "Malo")    AS malos,
            SUM(COALESCE(costo, 0))     AS valor_total
         FROM bienes WHERE area_id = ?'
    );
    $stmtStats->execute([$area_id]);
    $stats = $stmtStats->fetch();

    jsonResponse(['ok' => true, 'bienes' => $bienes, 'stats' => $stats]);
}

// ── GUARDAR FOTO ─────────────────────────────────────────────
function guardarFoto(string $key, ?string $fotoActual = null): ?string {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        return $fotoActual; // mantiene la foto anterior si no se sube nueva
    }

    $file = $_FILES[$key];

    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse(['ok' => false, 'error' => "La foto '$key' supera 5 MB"], 400);
    }

    $mime    = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowed, true)) {
        jsonResponse(['ok' => false, 'error' => "Formato de imagen no permitido en '$key'"], 400);
    }

    $ext      = match ($mime) {
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
    $nombre   = uniqid('foto_', true) . ".$ext";
    $destino  = UPLOADS_DIR . $nombre;

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        jsonResponse(['ok' => false, 'error' => "Error al guardar la imagen '$key'"], 500);
    }

    // Borra foto anterior si existe
    if ($fotoActual && file_exists(UPLOADS_DIR . basename($fotoActual))) {
        @unlink(UPLOADS_DIR . basename($fotoActual));
    }

    return UPLOADS_URL . $nombre;
}

// ── CREAR BIEN ───────────────────────────────────────────────
function actionCrear(): void {
    $area_id   = intval($_POST['area_id'] ?? 0);
    $etiqueta  = trim($_POST['no_etiqueta'] ?? '');
    $estado    = trim($_POST['estado_uso'] ?? '');

    if (!$area_id || !$etiqueta || !$estado) {
        jsonResponse(['ok' => false, 'error' => 'Faltan campos obligatorios'], 400);
    }

    $fotoInmueble = guardarFoto('foto_inmueble');
    $fotoEtiqueta = guardarFoto('foto_etiqueta');

    $sql = 'INSERT INTO bienes
               (area_id, no_etiqueta, caracteristicas, marca, modelo, numero_serie,
                estado_uso, costo, observaciones, observaciones_adicionales,
                observaciones_encargado, foto_inmueble, foto_etiqueta)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';

    $stmt = getDB()->prepare($sql);
    $stmt->execute([
        $area_id,
        $etiqueta,
        $_POST['caracteristicas']            ?? null,
        $_POST['marca']                      ?? null,
        $_POST['modelo']                     ?? null,
        $_POST['numero_serie']               ?? null,
        $estado,
        $_POST['costo']                      ?: null,
        $_POST['observaciones']              ?? null,
        $_POST['observaciones_adicionales']  ?? null,
        $_POST['observaciones_encargado']    ?? null,
        $fotoInmueble,
        $fotoEtiqueta,
    ]);

    jsonResponse(['ok' => true, 'id' => getDB()->lastInsertId()], 201);
}

// ── ACTUALIZAR BIEN ──────────────────────────────────────────
function actionActualizar(): void {
    $id        = intval($_POST['id'] ?? 0);
    $area_id   = intval($_POST['area_id'] ?? 0);
    $etiqueta  = trim($_POST['no_etiqueta'] ?? '');
    $estado    = trim($_POST['estado_uso'] ?? '');

    if (!$id || !$area_id || !$etiqueta || !$estado) {
        jsonResponse(['ok' => false, 'error' => 'Faltan campos obligatorios'], 400);
    }

    // Cargar fotos actuales
    $curr = getDB()->prepare('SELECT foto_inmueble, foto_etiqueta FROM bienes WHERE id = ? AND area_id = ?');
    $curr->execute([$id, $area_id]);
    $row = $curr->fetch();
    if (!$row) jsonResponse(['ok' => false, 'error' => 'Bien no encontrado'], 404);

    $fotoInmueble = guardarFoto('foto_inmueble', $row['foto_inmueble']);
    $fotoEtiqueta = guardarFoto('foto_etiqueta', $row['foto_etiqueta']);

    $sql = 'UPDATE bienes SET
               no_etiqueta = ?, caracteristicas = ?, marca = ?, modelo = ?,
               numero_serie = ?, estado_uso = ?, costo = ?,
               observaciones = ?, observaciones_adicionales = ?,
               observaciones_encargado = ?, foto_inmueble = ?, foto_etiqueta = ?
            WHERE id = ? AND area_id = ?';

    $stmt = getDB()->prepare($sql);
    $stmt->execute([
        $etiqueta,
        $_POST['caracteristicas']            ?? null,
        $_POST['marca']                      ?? null,
        $_POST['modelo']                     ?? null,
        $_POST['numero_serie']               ?? null,
        $estado,
        $_POST['costo']                      ?: null,
        $_POST['observaciones']              ?? null,
        $_POST['observaciones_adicionales']  ?? null,
        $_POST['observaciones_encargado']    ?? null,
        $fotoInmueble,
        $fotoEtiqueta,
        $id,
        $area_id,
    ]);

    jsonResponse(['ok' => true]);
}

// ── ELIMINAR BIEN ────────────────────────────────────────────
function actionEliminar(): void {
    $body    = json_decode(file_get_contents('php://input'), true);
    $id      = intval($body['id'] ?? 0);
    $area_id = intval($body['area_id'] ?? 0);

    if (!$id || !$area_id) jsonResponse(['ok' => false, 'error' => 'Datos incompletos'], 400);

    // Borra fotos físicas
    $curr = getDB()->prepare('SELECT foto_inmueble, foto_etiqueta FROM bienes WHERE id = ? AND area_id = ?');
    $curr->execute([$id, $area_id]);
    $row = $curr->fetch();
    if ($row) {
        foreach (['foto_inmueble', 'foto_etiqueta'] as $key) {
            if ($row[$key] && file_exists(UPLOADS_DIR . basename($row[$key]))) {
                @unlink(UPLOADS_DIR . basename($row[$key]));
            }
        }
    }

    $stmt = getDB()->prepare('DELETE FROM bienes WHERE id = ? AND area_id = ?');
    $stmt->execute([$id, $area_id]);

    jsonResponse(['ok' => true]);
}

// ── EXPORTAR (datos para Excel desde JS) ────────────────────
function actionExportar(): void {
    $area_id = intval($_GET['area_id'] ?? 0);
    if (!$area_id) jsonResponse(['ok' => false, 'error' => 'area_id requerido'], 400);

    $stmt = getDB()->prepare(
        'SELECT b.*, a.nombre AS area_nombre
         FROM bienes b
         JOIN areas a ON a.id = b.area_id
         WHERE b.area_id = ?
         ORDER BY b.no_etiqueta ASC'
    );
    $stmt->execute([$area_id]);
    $bienes = $stmt->fetchAll();

    jsonResponse(['ok' => true, 'bienes' => $bienes]);
}
