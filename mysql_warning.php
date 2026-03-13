<?php
// Script de advertencia para usuarios que intentan usar MySQL
echo "⚠️  ADVERTENCIA IMPORTANTE ⚠️\n";
echo str_repeat("=", 50) . "\n\n";

echo "El sistema hotel ya NO usa MySQL.\n";
echo "Ahora utiliza SQLite para evitar problemas de conexión.\n\n";

echo "Si estás viendo este error:\n";
echo "'SQLSTATE[HY000] [2002] No se puede establecer una conexión...'\n\n";

echo "Significa que estás ejecutando un script que intenta conectarse a MySQL.\n\n";

echo "✅ SOLUCIÓN:\n";
echo "1. El sistema YA ESTÁ FUNCIONANDO con SQLite\n";
echo "2. Accede a: http://localhost:8000\n";
echo "3. Usuario: admin@hotel.com\n";
echo "4. Contraseña: admin123\n\n";

echo "Para verificar que todo funciona:\n";
echo "php check_system.php\n\n";

echo "❌ NO EJECUTES scripts que contengan 'mysql' en el nombre\n";
echo "❌ NO EJECUTES: setup_mysql_db.php, create_tables_simple.php, etc.\n\n";

echo str_repeat("=", 50) . "\n";
echo "🎉 El sistema está completamente operativo con SQLite 🎉\n";