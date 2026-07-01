<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3>Agendar Nueva Cita</h3>
                    </div>
                    <div class="card-body p-4">
                        
                        <form action="procesar_cita.php" method="POST">
                            
                            <div class="mb-3">
                                <label for="servicio" class="form-label">Selecciona el Servicio:</label>
                            </div>
<select class="form-select" id="servicio" name="servicio_id" required>
    <option value="" disabled selected>-- Elige un servicio --</option>
    <?php
    // Configuración de la base de datos
    $host     = '127.0.0.1';
    $dbName   = 'sistema_citas';
    $username = 'root';
    $password = '';

    try {
        // Conexión limpia mediante PDO
        $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Consultamos los servicios que insertaste en phpMyAdmin
        $query = $pdo->query("SELECT id, nombre_servicio, precio FROM servicios");
        
        // Este bucle se encarga de crear un <option> por cada fila real en la base de datos
        while ($servicio = $query->fetch(PDO::FETCH_ASSOC)) {
            echo '<option value="' . $servicio['id'] . '">' . htmlspecialchars($servicio['nombre_servicio']) . ' ($' . $servicio['precio'] . ')</option>';
        }
    } catch (PDOException $e) {
        // Si hay algún problema, mostrará este aviso amigable en el desplegable
        echo '<option disabled>Error al cargar servicios desde la BD</option>';
    }
    ?>
</select>
                            <div class="mb-3">
                                <label for="fecha" class="form-label">Fecha de la Cita:</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required>
                            </div>

                            <div class="mb-3">
                                <label for="hora" class="form-label">Hora de la Cita:</label>
                                <select class="form-select" id="hora" name="hora" required>
                                    <option value="" disabled selected>-- Elige un horario --</option>
                                    <option value="09:00:00">09:00 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="14:00:00">02:00 PM</option>
                                    <option value="15:00:00">03:00 PM</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-block">Confirmar Reserva</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>