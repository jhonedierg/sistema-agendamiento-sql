<?php
$dbName   = 'sistema_citas';
$username = 'root';
$password = ''; 

// Le decimos a PHP la ubicación exacta del archivo socket que vimos en tu configuración
$socket   = "C:/xampp/mysql/mysql.sock";

try {
    // Construimos la conexión usando "unix_socket" (que en Windows funciona para XAMPP MariaDB)
    // Esto puentea las restricciones de red locales
    $dsn = "mysql:unix_socket=$socket;dbname=$dbName;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si por alguna razón tu versión de PHP bajo Windows no reconoce 'unix_socket' de forma directa,
    // usamos el método alternativo que limpia el host forzando localhost puro en puerto limpio:
    try {
        $pdo = new PDO("mysql:host=localhost;port=3306;dbname=$dbName;charset=utf8mb4", $username, $password, [
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e2) {
        die("Error de conexión al socket de XAMPP: " . $e2->getMessage());
    }
}

// ... de aquí para abajo sigue tu código original ...

// ... de aquí para abajo dejas el resto de tu código igual ...
// ... resto de tu código ...}

// 2. Verificar que los datos vengan por el método POST
// 2. Verificar que los datos vengan por el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // [PRIMERO]: Recogemos los datos del formulario
    $servicio_id = $_POST['servicio_id'];
    $fecha       = $_POST['fecha'];
    $hora        = $_POST['hora'];
    
    // NOTA: Como aún no tenemos login, asignaremos la cita al usuario con ID 1
    $usuario_id  = 1; 

    // [SEGUNDO]: Hacer la consulta en la base de datos para verificar disponibilidad
    $sql_verificar = "SELECT COUNT(*) FROM citas WHERE fecha = :fecha AND hora = :hora";
    $stmt_verificar = $pdo->prepare($sql_verificar);
    $stmt_verificar->execute([
        ':fecha' => $fecha,
        ':hora'  => $hora
    ]);
    
    // Aquí es donde se crea la variable que te daba error
    $citas_existentes = $stmt_verificar->fetchColumn();

    // [TERCERO]: Ahora que la variable ya existe, hacemos la pregunta (IF)
    if ($citas_existentes > 0) {
        // Si el resultado es mayor a 0, significa que el horario ya está ocupado
        echo "<div style='color: red; font-family: Arial;'>Lo sentimos, este horario ya está reservado...</div>";
        echo "<br><a href='index.php'>Volver al formulario</a>";
    } else {
        // [CUARTO]: Si el horario está libre, procedemos a guardar de forma segura
        try {
            $sql_insertar = "INSERT INTO citas (usuario_id, servicio_id, fecha, hora, estado) 
                             VALUES (:usuario_id, :servicio_id, :fecha, :hora, 'pendiente')";
            
            $stmt_insertar = $pdo->prepare($sql_insertar);
            
            $stmt_insertar->execute([
                ':usuario_id'  => $usuario_id,
                ':servicio_id' => $servicio_id,
                ':fecha'       => $fecha,
                ':hora'        => $hora
            ]);

            // Mensaje de éxito si todo salió bien
            echo "<div style='color: green; font-family: Arial; font-weight: bold;'>¡Cita agendada con éxito!</div>";
            echo "<br><a href='index.php'>Agendar otra cita</a>";

        } catch (PDOException $e) {
            echo "Error al guardar la cita: " . $e->getMessage();
        }
    }
} else {
    // Si alguien intenta entrar a este archivo directamente sin enviar el formulario
    header("Location: index.php");
    exit();
}
?>