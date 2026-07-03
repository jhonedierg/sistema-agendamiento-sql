<?php
$dbName   = 'sistema_citas';
$username = 'root';
$password = ''; 
$socket   = "C:/xampp/mysql/mysql.sock";

try {
    $dsn = "mysql:unix_socket=$socket;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=localhost;port=3306;dbname=$dbName;charset=utf8mb4", $username, $password, [
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e2) {
        die("Error de conexión: " . $e2->getMessage());
    }
}

$mostrar_calendario = false;
$nueva_cita_fecha = '';
$nueva_cita_hora = '';
$nueva_cita_id = '';
$nueva_cita_servicio = '';
$mensaje_alerta = '';
$tipo_alerta = '';

// --- ACCIÓN: ELIMINAR CITA ---
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) {
    try {
        $sql_eliminar = "DELETE FROM citas WHERE id = :id";
        $stmt = $pdo->prepare($sql_eliminar);
        $stmt->execute([':id' => $_GET['id']]);
        header("Location: Procesar_cita.php?mensaje=eliminado");
        exit();
    } catch (PDOException $e) {
        die("Error al eliminar la cita: " . $e->getMessage());
    }
}

// --- ACCIÓN: EDITAR CITA (PROCESAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id             = $_POST['id'];
    $servicio_id    = $_POST['servicio_id'];
    $profesional_id = $_POST['profesional_id'];
    $fecha          = $_POST['fecha'];
    $hora           = $_POST['hora'];

    try {
        // Valida que el nuevo horario no choque PARA EL MISMO profesional
        $sql_verificar = "SELECT COUNT(*) FROM citas WHERE fecha = :fecha AND hora = :hora AND profesional_id = :profesional_id AND id != :id";
        $stmt = $pdo->prepare($sql_verificar);
        $stmt->execute([':fecha' => $fecha, ':hora' => $hora, ':profesional_id' => $profesional_id, ':id' => $id]);
        
        if ($stmt->fetchColumn() > 0) {
            $mensaje_alerta = "Lo sentimos, ese profesional ya tiene un turno asignado en ese horario.";
            $tipo_alerta = "danger";
        } else {
            $sql_actualizar = "UPDATE citas SET servicio_id = :servicio_id, profesional_id = :profesional_id, fecha = :fecha, hora = :hora WHERE id = :id";
            $stmt = $pdo->prepare($sql_actualizar);
            $stmt->execute([':servicio_id' => $servicio_id, ':profesional_id' => $profesional_id, ':fecha' => $fecha, ':hora' => $hora, ':id' => $id]);
            header("Location: Procesar_cita.php?mensaje=actualizado");
            exit();
        }
    } catch (PDOException $e) {
        die("Error al actualizar: " . $e->getMessage());
    }
}

// --- ACCIÓN: CREAR CITA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['accion'])) {
    $servicio_id    = $_POST['servicio_id'];
    $profesional_id = $_POST['profesional_id'];
    $fecha          = $_POST['fecha'];
    $hora           = $_POST['hora'];
    $usuario_id     = 1; 

    // Verifica disponibilidad basándose en fecha, hora Y barbero escogido
    $sql_verificar = "SELECT COUNT(*) FROM citas WHERE fecha = :fecha AND hora = :hora AND profesional_id = :profesional_id";
    $stmt_verificar = $pdo->prepare($sql_verificar);
    $stmt_verificar->execute([':fecha' => $fecha, ':hora' => $hora, ':profesional_id' => $profesional_id]);
    
    if ($stmt_verificar->fetchColumn() > 0) {
        $mensaje_alerta = "Lo sentimos, este barbero ya está reservado a esa hora. Elige otro horario o barbero.";
        $tipo_alerta = "danger";
    } else {
        try {
            $sql_insertar = "INSERT INTO citas (usuario_id, servicio_id, profesional_id, fecha, hora, estado) 
                             VALUES (:usuario_id, :servicio_id, :profesional_id, :fecha, :hora, 'pendiente')";
            $stmt_insertar = $pdo->prepare($sql_insertar);
            $stmt_insertar->execute([':usuario_id' => $usuario_id, ':servicio_id' => $servicio_id, ':profesional_id' => $profesional_id, ':fecha' => $fecha, ':hora' => $hora]);
            
            $mostrar_calendario = true;
            $nueva_cita_id = $pdo->lastInsertId();
            $nueva_cita_fecha = $fecha;
            $nueva_cita_hora = $hora;
            $nueva_cita_servicio = $servicio_id;
            
            $mensaje_alerta = "¡Turno agendado con éxito en la barbería!";
            $tipo_alerta = "success";
        } catch (PDOException $e) {
            die("Error al guardar la cita: " . $e->getMessage());
        }
    }
}

if(isset($_GET['mensaje'])){
    if($_GET['mensaje'] === 'eliminado') { $mensaje_alerta = "El turno ha sido cancelado."; $tipo_alerta = "warning"; }
    if($_GET['mensaje'] === 'actualizado') { $mensaje_alerta = "El turno se actualizó de manera exitosa."; $tipo_alerta = "success"; }
}

// --- LOGICA DE ESPACIOS VACÍOS GENERALES ---
$fecha_consulta = isset($_GET['fecha_busqueda']) ? $_GET['fecha_busqueda'] : date('Y-m-d');
$horas_laborales = ['08:00:00', '09:00:00', '10:00:00', '11:00:00', '14:00:00', '15:00:00', '16:00:00'];

// Traemos las horas ocupadas globales de la fecha seleccionada
$stmt_ocupadas = $pdo->prepare("SELECT hora FROM citas WHERE fecha = :fecha");
$stmt_ocupadas->execute([':fecha' => $fecha_consulta]);
$horas_ocupadas = $stmt_ocupadas->fetchAll(PDO::FETCH_COLUMN);
$espacios_vacios = array_diff($horas_laborales, $horas_ocupadas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Gestión de Turnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .badge-vacio { background-color: #e8f5e9; color: #2e7d32; font-weight: 600; padding: 10px 16px; border-radius: 20px; display: inline-block; margin: 6px; font-size: 15px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-custom py-3 shadow">
        <div class="container">
            <span class="navbar-brand mb-0 h1 fs-3"><i class="fa-solid fa-calendar-plus me-2"></i> Control de Turnos</span>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-plus me-1"></i> Nuevo Turno</a>
        </div>
    </nav>

    <div class="container my-5">
        
        <?php if(!empty($mensaje_alerta)): ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="fa-solid <?php echo $tipo_alerta === 'success' ? 'fa-circle-check' : ($tipo_alerta === 'danger' ? 'fa-triangle-exclamation' : 'fa-trash-can'); ?> fs-4 me-3"></i>
                <div><?php echo $mensaje_alerta; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="this.parentElement.style.display='none';"></button>
            </div>
        <?php endif; ?>

        <?php if($mostrar_calendario): ?>
            <div class="card mb-5 border-success border-start border-4 shadow-sm bg-white">
                <div class="card-body p-4">
                    <div class="d-md-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="text-success fw-bold mb-1"><i class="fa-solid fa-circle-check me-2"></i>¡Guardado en Agenda!</h4>
                            <p class="text-muted mb-0">Revisa tu espacio reservado en el calendario:</p>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <a href="Procesar_cita.php?accion=vista_editar&id=<?php echo $nueva_cita_id; ?>" class="btn btn-primary btn-sm me-2 shadow-sm"><i class="fa-solid fa-pen-to-square me-1"></i> Corregir</a>
                            <a href="Procesar_cita.php?accion=eliminar&id=<?php echo $nueva_cita_id; ?>" class="btn btn-danger btn-sm shadow-sm" onclick="return confirm('¿Cancelar turno recién creado?');"><i class="fa-solid fa-trash me-1"></i> Cancelar</a>
                        </div>
                    </div>
                    <div id="calendario-registro" class="shadow-sm border rounded bg-white" style="max-width: 700px; margin: 0 auto; padding: 15px;"></div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var calendarEl = document.getElementById('calendario-registro');
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'timeGridDay',
                        initialDate: '<?php echo $nueva_cita_fecha; ?>',
                        locale: 'es',
                        slotMinTime: '07:00:00',
                        slotMaxTime: '19:00:00',
                        allDaySlot: false,
                        headerToolbar: { left: '', center: 'title', right: '' },
                        events: [{
                            id: '<?php echo $nueva_cita_id; ?>',
                            title: 'Tu Turno (Servicio #<?php echo $nueva_cita_servicio; ?>)',
                            start: '<?php echo $nueva_cita_fecha . "T" . $nueva_cita_hora; ?>',
                            color: '#198754',
                            textColor: '#ffffff'
                        }]
                    });
                    calendar.render();
                });
            </script>
        <?php endif; ?>

        <?php if (isset($_GET['accion']) && $_GET['accion'] === 'vista_editar' && isset($_GET['id'])): 
            $stmt_cita = $pdo->prepare("SELECT * FROM citas WHERE id = :id");
            $stmt_cita->execute([':id' => $_GET['id']]);
            $cita_editar = $stmt_cita->fetch(PDO::FETCH_ASSOC);
            if($cita_editar):
        ?>
            <div class="card mb-5 border-primary border-start border-4 shadow-sm bg-white">
                <div class="card-header bg-transparent py-3">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Modificar Turno Interno (ID: <?php echo $cita_editar['id']; ?>)</h5>
                </div>
                <div class="card-body">
                    <form action="Procesar_cita.php" method="POST" class="row g-3">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" value="<?php echo $cita_editar['id']; ?>">
                        
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">ID del Servicio</label>
                            <input type="number" class="form-control" name="servicio_id" value="<?php echo $cita_editar['servicio_id']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Profesional</label>
                            <select class="form-select" name="profesional_id" required>
                                <?php
                                $qP = $pdo->query("SELECT id, nombre FROM profesionales");
                                while($p = $qP->fetch(PDO::FETCH_ASSOC)) {
                                    $sel = ($p['id'] == $cita_editar['profesional_id']) ? 'selected' : '';
                                    echo "<option value='{$p['id']}' {$sel}>{$p['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Fecha Reservada</label>
                            <input type="date" class="form-control" name="fecha" value="<?php echo $cita_editar['fecha']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Hora Reservada</label>
                            <input type="time" class="form-control" name="hora" value="<?php echo $cita_editar['hora']; ?>" required>
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <a href="Procesar_cita.php" class="btn btn-light rounded-pill px-4 me-2">Descartar</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; endif; ?>

        <div class="row g-4">
            <div class="col-12">
                <div class="card bg-white shadow-sm">
                    <div class="card-header bg-transparent pt-4 pb-2 border-0 text-center">
                        <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>Buscar Huecos Disponibles globales</h4>
                        <p class="text-muted small mt-1">Selecciona una fecha para ver qué horas están completamente limpias.</p>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center pb-5">
                        <form method="GET" action="Procesar_cita.php" class="mb-4" style="max-width: 400px; width: 100%;">
                            <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                                <input type="date" class="form-control border-0 px-3" name="fecha_busqueda" value="<?php echo $fecha_consulta; ?>">
                                <button class="btn btn-primary px-4" type="submit"><i class="fa-solid fa-search"></i></button>
                            </div>
                        </form>

                        <p class="text-muted fw-semibold mb-3">Horas en las que ningún barbero está ocupado: <span class="badge bg-secondary px-3 py-2 fs-6 rounded-pill"><?php echo $fecha_consulta; ?></span></p>
                        
                        <div class="d-flex flex-wrap justify-content-center mt-2" style="max-width: 800px;">
                            <?php if(count($espacios_vacios) > 0): ?>
                                <?php foreach($espacios_vacios as $libre): ?>
                                    <span class="badge-vacio shadow-sm"><i class="fa-regular fa-clock me-2"></i><?php echo substr($libre, 0, 5); ?> hs</span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-light border text-center w-100 py-4 shadow-sm" role="alert" style="max-width: 500px; border-radius: 12px;">
                                    <i class="fa-solid fa-circle-exclamation text-warning fs-1 mb-2"></i>
                                    <h5 class="fw-bold mb-1">¡Agenda Llena!</h5>
                                    <p class="mb-0 small text-muted">Todos los barberos tienen turnos asignados hoy.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>