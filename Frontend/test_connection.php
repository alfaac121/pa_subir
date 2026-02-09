<?php
/**
 * Test de conexión a la base de datos
 */

require_once 'config.php';

echo "=== TEST DE CONEXIÓN A BASE DE DATOS ===\n\n";

try {
    $conn = getDBConnection();
    
    if ($conn) {
        echo "✅ Conexión exitosa a la base de datos: " . DB_NAME . "\n\n";
        
        // Verificar tablas
        $result = $conn->query("SHOW TABLES");
        echo "📋 Tablas encontradas: " . $result->num_rows . "\n\n";
        
        echo "Lista de tablas:\n";
        echo "----------------\n";
        while ($row = $result->fetch_array()) {
            echo "  • " . $row[0] . "\n";
        }
        
        echo "\n📊 Datos pre-cargados:\n";
        echo "----------------\n";
        
        // Contar registros en tablas importantes
        $tables = [
            'categorias' => 'Categorías',
            'subcategorias' => 'Subcategorías', 
            'estados' => 'Estados',
            'roles' => 'Roles',
            'integridad' => 'Tipos de integridad',
            'motivos' => 'Motivos (PQRS/Notif)',
            'sucesos' => 'Sucesos (Auditoría)'
        ];
        
        foreach ($tables as $table => $label) {
            $count = $conn->query("SELECT COUNT(*) as total FROM $table")->fetch_assoc()['total'];
            echo "  • $label: $count registros\n";
        }
        
        echo "\n✅ Todo funcionando correctamente!\n";
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
