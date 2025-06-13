# 🔧 Dev-Tools Auto-Installer - Guía de Uso

Script inteligente para la instalación automática de submódulos Dev-Tools con detección automática de estado.

## 🎯 ¿Qué hace el script?

El script `install-dev-tools.sh` automatiza completamente la configuración de Dev-Tools:

1. **Detecta automáticamente** si el submódulo existe o necesita inicialización
2. **Ejecuta automáticamente** `git submodule init && git submodule update` cuando es necesario
3. **Instala completamente** Dev-Tools con todas sus dependencias
4. **Valida la instalación** para asegurar que todo funciona correctamente

## 🚀 Uso Básico

### Opción 1: Script Automático (Recomendado)
```bash
# Desde el directorio raíz del plugin
./install-dev-tools.sh
```

### Opción 2: Comandos Manuales (Si prefieres control paso a paso)
```bash
# 1. Inicializar submódulo si es necesario
git submodule init
git submodule update

# 2. Instalar Dev-Tools
cd dev-tools
./install.sh

# 3. Validar instalación
./validate.sh
```

## 🔍 Detección Inteligente de Estado

El script detecta automáticamente diferentes estados:

### ✅ Submódulo Ya Inicializado
```
🔍 Verificando estado de submódulos...
✅ Submódulo dev-tools ya está inicializado
✅ Submódulo dev-tools configurado correctamente
```

### 📦 Submódulo No Inicializado (Directorio Vacío)
```
🔍 Verificando estado de submódulos...
📦 Directorio dev-tools existe pero está vacío - Inicializando submódulo...
✅ Submódulo dev-tools configurado correctamente
```

### 📦 Submódulo No Existe
```
🔍 Verificando estado de submódulos...
📦 Directorio dev-tools no existe - Inicializando submódulo...
✅ Submódulo dev-tools configurado correctamente
```

## 🛠️ Funcionalidades del Script

### Verificaciones de Sistema
- ✅ Git disponible y repositorio válido
- ✅ Node.js, npm, PHP, Composer detectados
- ✅ Archivo .gitmodules válido

### Configuración Automática
- ✅ Inicialización de submódulos Git
- ✅ Instalación de dependencias Node.js
- ✅ Instalación de dependencias PHP/Composer
- ✅ Compilación de assets (CSS/JS)
- ✅ Configuración de PHPUnit

### Validación Completa
- ✅ Verificación de estructura de archivos
- ✅ Tests de Webpack y bundling
- ✅ Tests de PHPUnit y autoloader
- ✅ Generación de reporte de validación

## 📋 Casos de Uso

### 1. Primera Instalación
```bash
# Clone del repositorio principal
git clone <tu-repo>
cd tu-proyecto

# El directorio dev-tools estará vacío/ausente
./install-dev-tools.sh
# ✅ Automáticamente inicializa submódulo e instala todo
```

### 2. Después de git pull (submódulo actualizado)
```bash
git pull origin main
# Si dev-tools se actualizó pero no está sincronizado

./install-dev-tools.sh
# ✅ Automáticamente sincroniza e instala actualizaciones
```

### 3. Reset del Entorno de Desarrollo
```bash
# Limpiar todo y reinstalar
rm -rf dev-tools
./install-dev-tools.sh
# ✅ Automáticamente recrea y configura todo
```

## 🔧 Personalización

### Variables de Entorno
```bash
# Personalizar ubicación del submódulo (por defecto: dev-tools)
DEV_TOOLS_DIR="custom-tools" ./install-dev-tools.sh

# Skip validación (instalación más rápida)
SKIP_VALIDATION=true ./install-dev-tools.sh
```

### Modificación del Script
El script está diseñado para ser fácilmente personalizable:

```bash
# Editar configuraciones
nano install-dev-tools.sh

# Variables principales a personalizar:
PROJECT_ROOT="$SCRIPT_DIR"              # Directorio base
DEV_TOOLS_DIR="$PROJECT_ROOT/dev-tools" # Directorio del submódulo
```

## 🆚 Comparación: Antes vs Después

### ❌ Proceso Manual Anterior
```bash
# El usuario tenía que recordar varios pasos
git submodule init
git submodule update
cd dev-tools
./install.sh
./validate.sh
cd ..
# Múltiples comandos, propenso a errores
```

### ✅ Proceso Automático Actual
```bash
# Un solo comando hace todo
./install-dev-tools.sh
# Detección automática + instalación completa
```

## 🎯 Próximas Mejoras

### Integración con AI Assistant
Cuando el usuario escriba:
- "instala el submódulo"
- "configura dev-tools"  
- "inicializa el entorno de desarrollo"

El AI Assistant automáticamente:
1. **Detectará** la estructura del proyecto
2. **Ejecutará** `./install-dev-tools.sh`
3. **Reportará** el resultado al usuario

### Comandos Adicionales Planificados
```bash
./install-dev-tools.sh --update    # Solo actualizar submódulo
./install-dev-tools.sh --validate  # Solo validar instalación
./install-dev-tools.sh --reset     # Reset completo del entorno
```

## 📚 Referencias

- **Submódulos Git**: [Git Submodules Documentation](https://git-scm.com/book/en/v2/Git-Tools-Submodules)
- **Dev-Tools Arquitectura 3.0**: `dev-tools/docs/`
- **Instalación Manual**: `dev-tools/INSTALL.md`
- **Validación**: `dev-tools/validate.sh`

---

**Nota**: Este script es parte del sistema Dev-Tools Arquitectura 3.0 y está diseñado para ser robusto, inteligente y fácil de usar para cualquier desarrollador.
