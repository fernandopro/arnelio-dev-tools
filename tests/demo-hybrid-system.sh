#!/bin/bash
# Demo del Sistema Híbrido Anti-Deadlock
# Este script demuestra cómo controlar el comportamiento de DevToolsTestCase

echo "🎯 DEMOSTRACIÓN: Sistema Híbrido DevToolsTestCase"
echo "=============================================="
echo ""

# Cambiar al directorio de tests
cd "$(dirname "$0")"

echo "📋 OPCIONES DE CONTROL DISPONIBLES:"
echo ""
echo "1️⃣  MODO AUTOMÁTICO (por defecto)"
echo "   - Detecta automáticamente contextos problemáticos"
echo "   - Usa anti-deadlock solo cuando es necesario"
echo "   ./run-tests.sh"
echo ""

echo "2️⃣  FORZAR MODO ESTÁNDAR WORDPRESS (sin protecciones)"
echo "   - 100% comportamiento oficial de WordPress"
echo "   - Útil para verificar compatibilidad"
echo "   DEV_TOOLS_FORCE_ANTI_DEADLOCK=0 ./run-tests.sh"
echo ""

echo "3️⃣  FORZAR MODO ANTI-DEADLOCK (con protecciones)"
echo "   - Siempre usar protecciones anti-deadlock"
echo "   - Útil para tests problemáticos conocidos"
echo "   DEV_TOOLS_FORCE_ANTI_DEADLOCK=1 ./run-tests.sh"
echo ""

echo "4️⃣  TESTS MASIVOS VÍA AJAX (auto-detecta como riesgoso)"
echo "   - Simula ejecución desde panel web"
echo "   - Automáticamente activa protecciones"
echo "   DOING_AJAX=1 ./run-tests.sh"
echo ""

echo "🔧 CONTROL DESDE CÓDIGO PHP:"
echo ""
echo "class MiTest extends DevToolsTestCase {"
echo "    protected function setUp(): void {"
echo "        parent::setUp();"
echo "        // Forzar modo estándar para este test específico"
echo "        \$this->useStandardWordPressBehavior();"
echo "    }"
echo "}"
echo ""

echo "🔍 VERIFICAR CONTEXTO ACTUAL:"
echo ""
echo "class MiTest extends DevToolsTestCase {"
echo "    public function testVerificarContexto(): void {"
echo "        \$context = \$this->getTestContext();"
echo "        \$this->assertArrayHasKey('anti_deadlock_active', \$context);"
echo "        echo 'Modo activo: ' . (\$context['anti_deadlock_active'] ? 'Anti-deadlock' : 'Estándar');"
echo "    }"
echo "}"
echo ""

echo "📊 ESTADÍSTICAS DE USO:"
echo "----------------------"

# Contar tests que usan DevToolsTestCase vs WP_UnitTestCase
DEVTOOLS_TESTS=$(find . -name "*.php" -exec grep -l "extends DevToolsTestCase" {} \; 2>/dev/null | wc -l | tr -d ' ')
STANDARD_TESTS=$(find . -name "*.php" -exec grep -l "extends WP_UnitTestCase" {} \; 2>/dev/null | wc -l | tr -d ' ')

echo "Tests usando DevToolsTestCase: $DEVTOOLS_TESTS"
echo "Tests usando WP_UnitTestCase: $STANDARD_TESTS"
echo ""

if [ "$DEVTOOLS_TESTS" -gt 0 ]; then
    echo "✅ Sistema híbrido ACTIVO"
    echo "   - Los tests pueden usar protecciones anti-deadlock cuando sea necesario"
    echo "   - Mantiene compatibilidad total con WordPress oficial"
else
    echo "ℹ️  Sistema híbrido DISPONIBLE pero no usado"
    echo "   - Para activar, cambia 'extends WP_UnitTestCase' por 'extends DevToolsTestCase'"
fi

echo ""
echo "🎛️  COMANDOS DE PRUEBA:"
echo "---------------------"
echo ""

echo "# Ejecutar con modo automático (recomendado)"
echo "./run-tests.sh"
echo ""

echo "# Forzar modo estándar WordPress (sin protecciones)"
echo "DEV_TOOLS_FORCE_ANTI_DEADLOCK=0 ./run-tests.sh"
echo ""

echo "# Forzar modo anti-deadlock (con protecciones)"
echo "DEV_TOOLS_FORCE_ANTI_DEADLOCK=1 ./run-tests.sh"
echo ""

echo "# Simular tests masivos vía AJAX (auto-activa protecciones)"
echo "DOING_AJAX=1 DEV_TOOLS_MASS_TESTS=1 ./run-tests.sh"
echo ""

echo "# Test específico con diagnóstico"
echo "./vendor/bin/phpunit tests/integration/DevToolsDatabaseAndTransientsTest.php --verbose"
echo ""

echo "🔄 MIGRACIÓN GRADUAL:"
echo "--------------------"
echo "1. Cambiar 'extends WP_UnitTestCase' → 'extends DevToolsTestCase' en tests problemáticos"
echo "2. Tests individuales seguirán usando comportamiento estándar automáticamente"
echo "3. Tests masivos activarán protecciones automáticamente"
echo "4. Control manual disponible en tests específicos que lo requieran"
echo ""

echo "✅ El sistema híbrido preserva compatibilidad total con WordPress"
echo "✅ No afecta futuras actualizaciones del framework oficial"
echo "✅ Permite control granular según necesidades específicas"
