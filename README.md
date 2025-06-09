# Dev Tools Arquitectura 3.0

Sistema avanzado de herramientas de desarrollo para WordPress con **Sistema de Debug WordPress Dinámico** integrado.

## 🔍 **NUEVO: Sistema de Debug WordPress Dinámico**

**¡Revoluciona tu debugging de WordPress!** Herramienta integrada en el núcleo que elimina los problemas de URLs dinámicas y debugging manual.

### ⚡ **Debug Instantáneo**
```
?debug_config=1    # Configuración completa
?debug_urls=1      # Análisis inteligente de URLs
```

### 🎯 **Características Destacadas**
- ✅ **Debug visual instantáneo** sin tocar código
- ✅ **Análisis automático de URLs** con recomendaciones
- ✅ **API programática completa** para integración
- ✅ **Plugin-agnóstico** - Funciona en cualquier proyecto
- ✅ **Documentación exhaustiva** incluida

> 📖 **[Ver documentación completa →](docs/DEBUG-WORDPRESS-DYNAMIC.md)**  
> 🚀 **[Guía de promoción →](docs/PROMOCION-DEBUG-WORDPRESS-DINAMICO.md)**

---

## 🏗️ **Arquitectura 3.0**

Sistema modular completo con 6 módulos implementados y funcionales:
- Dashboard, SystemInfo, Cache, AjaxTester, Logs, Performance

## Uso como Submódulo Git

Este repositorio está diseñado para ser usado como submódulo Git en proyectos WordPress.

### Instalación
```bash
git submodule add [URL-DEL-REPOSITORIO] dev-tools
cd dev-tools && ./install.sh
```

### Actualización
```bash
git submodule update --remote dev-tools
```

### Verificación del Sistema
```bash
cd dev-tools && ./verify-debug-system.sh
```

---

**Última actualización: 9 de junio de 2025** - Sistema de Debug WordPress Dinámico integrado

