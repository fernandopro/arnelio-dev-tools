# 🚀 Regenerar Plugin-Dev-Tools - Guía Rápida

## ⚡ Instalación Completa (NUEVA - Automática)
```bash
# Desde el directorio del plugin principal
./install-dev-tools.sh
```
**Resultado**: Instala dev-tools Y crea plugin-dev-tools automáticamente

## Comandos para Recrear Solo la Estructura Override

### ⚡ Método Rápido (Recomendado)
```bash
cd dev-tools
composer override:create
```

### 🔧 Método Directo
```bash
cd dev-tools
php scripts/create-override-structure.php
```

### 📋 Lo que se Crea Automáticamente

```
plugin-dev-tools/
├── tests/
│   ├── database/     ✅ Replica exacta de dev-tools/tests/
│   ├── includes/     ✅ 
│   ├── integration/  ✅
│   ├── modules/      ✅
│   └── unit/         ✅
├── config/
├── phpunit.xml       ✅ Configurado para tests específicos + framework
├── .gitignore        ✅ 
└── README.md         ✅
```

### 🧪 Verificar que Funciona
```bash
cd dev-tools
composer test:plugin
```

**Resultado esperado**: 92+ tests ejecutándose correctamente

### 📖 Documentación Completa
Ver: `dev-tools/docs/SISTEMA-OVERRIDE-TESTING.md`

---
*Sistema implementado: Junio 2025 - Dev-Tools v3.0*
