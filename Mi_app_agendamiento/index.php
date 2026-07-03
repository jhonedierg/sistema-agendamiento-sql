<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Nueva Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-gradient { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; transition: all 0.3s ease; }
        .btn-gradient:hover { background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%); color: white; transform: translateY(-2px); }
        .form-control:focus, .form-select:focus { border-color: #2a5298; box-shadow: 0 0 0 0.25px rgba(42, 82, 152, 0.25); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-custom py-3 shadow">
        <div class="container">
            <span class="navbar-brand mb-0 h1 fs-3"><i class="fa-solid fa-calendar-plus me-2"></i> Sistema de Reservas</span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                
                <div class="card bg-white p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-user-check fs-2 text-primary"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Reserva tu Turno</h3>
                        <p class="text-muted small">Completa los siguientes datos para agendar tu corte o servicio de inmediato.</p>
                    </div>

                    <form action="procesar_cita.php" method="POST" class="needs-validation" novalidate>
                        
                        <?php
                        // Configuración única de BD para conectar ambos bloques dinámicos
                        $host     = '127.0.0.1';
                        $dbName   = 'sistema_citas';
                        $username = 'root';
                        $password = '';
                        try {
                            $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        } catch (PDOException $e) {
                            die('<div class="alert alert-danger">Error de conexión a la BD</div>');
                        }
                        ?>

                        <div class="mb-3">
                            <label for="servicio" class="form-label fw-semibold"><i class="fa-solid fa-scissors me-2 text-secondary"></i>Selecciona el Servicio:</label>
                            <select class="form-select py-2.5 rounded-3" id="servicio" name="servicio_id" required>
                                <option value="" disabled selected>-- Elige un servicio --</option>
                                <?php
                                $queryServ = $pdo->query("SELECT id, nombre_servicio, precio FROM servicios");
                                while ($servicio = $queryServ->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $servicio['id'] . '">' . htmlspecialchars($servicio['nombre_servicio']) . ' ($' . $servicio['precio'] . ')</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="profesional" class="form-label fw-semibold"><i class="fa-solid fa-id-card me-2 text-secondary"></i>Selecciona el Barbero / Profesional:</label>
                            <select class="form-select py-2.5 rounded-3" id="profesional" name="profesional_id" required>
                                <option value="" disabled selected>-- Elige a tu profesional favorito --</option>
                                <?php
                                $queryProf = $pdo->query("SELECT id, nombre FROM profesionales");
                                while ($prof = $queryProf->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $prof['id'] . '">' . htmlspecialchars($prof['nombre']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label for="fecha" class="form-label fw-semibold"><i class="fa-regular fa-calendar-days me-2 text-secondary"></i>Fecha de la Cita:</label>
                                <input type="date" class="form-control py-2.5 rounded-3" id="fecha" name="fecha" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-sm-6">
                                <label for="hora" class="form-label fw-semibold"><i class="fa-regular fa-clock me-2 text-secondary"></i>Hora de la Cita:</label>
                                <select class="form-select py-2.5 rounded-3" id="hora" name="hora" required>
                                    <option value="" disabled selected>-- Elige un horario --</option>
                                    <option value="09:00:00">09:00 AM</option>
                                    <option value="10:00:00">10:00 AM</option>
                                    <option value="11:00:00">11:00 AM</option>
                                    <option value="14:00:00">02:00 PM</option>
                                    <option value="15:00:00">03:00 PM</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-gradient rounded-pill py-3 fw-bold shadow-sm">
                                <i class="fa-solid fa-check me-2"></i>Confirmar Reserva
                            </button>
                        </div>
                        
                    </form>
                </div>
                
                <p class="text-center mt-4 text-muted small">¿Quieres revisar la disponibilidad antes de agendar? <a href="Procesar_cita.php" class="text-decoration-none fw-semibold">Consultar espacios libres</a></p>
                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
          'use strict'
          const forms = document.querySelectorAll('.needs-validation')
          Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
              if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
              }
              form.classList.add('was-validated')
            }, false)
          })
        })()
    </script>
</body>
</html>