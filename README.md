# Learning Style Survey - Plugin para Moodle

🎯 **Plugin de Aprendizaje Adaptativo basado en Estilos de Aprendizaje**

Este plugin para Moodle implementa un sistema completo de aprendizaje adaptativo que identifica automáticamente el estilo de aprendizaje de cada estudiante mediante el cuestionario ILS (Index of Learning Styles) de Felder-Silverman, y posteriormente les proporciona rutas de aprendizaje personalizadas con recursos y evaluaciones adaptadas a su perfil individual.

## 🌟 Características Principales

### Para Estudiantes
- 📝 **Encuesta ILS de 44 preguntas** para detectar estilo de aprendizaje dominante
- 🛤️ **Rutas de aprendizaje personalizadas** basadas en su estilo detectado
- 📚 **Recursos filtrados automáticamente** (visual, verbal, activo, reflexivo, etc.)
- 📊 **Progreso visual** de su avance en la ruta
- 🔄 **Navegación adaptativa** que se ajusta según rendimiento en evaluaciones
- 💡 **Recursos de refuerzo** automáticos para temas con dificultades

### Para Profesores
- 👥 **Dashboard de progreso** de todos los estudiantes
- 📁 **Gestión de recursos** por estilo de aprendizaje y tema
- 📝 **Creador de evaluaciones** con navegación condicional
- 🛠️ **Editor de rutas** drag-and-drop para organizar secuencias
- 📈 **Reportes detallados** de rendimiento y estilos detectados
- 🎯 **Configuración de saltos** adaptativos basados en resultados

### Para Administradores
- 🔧 **Herramientas de diagnóstico** completas del sistema
- 📊 **Estadísticas globales** de uso y rendimiento
- 🛡️ **Sistema de permisos** integrado con roles de Moodle
- 🔍 **Verificación de funcionalidades** automática

## 🧠 Modelo de Estilos de Aprendizaje

Implementa el modelo **Felder-Silverman** con 4 dimensiones:

| Dimensión | Estilo A | Estilo B | Descripción |
|-----------|----------|----------|-------------|
| **Procesamiento** | Activo | Reflexivo | ¿Cómo se procesa la información? |
| **Percepción** | Sensorial | Intuitivo | ¿Qué tipo de información se prefiere? |
| **Entrada** | Visual | Verbal | ¿Cómo se recibe mejor la información? |
| **Comprensión** | Secuencial | Global | ¿En qué orden se entiende la información? |

## 🚀 Instalación Rápida

1. **Descargar** el plugin desde GitHub
2. **Acceder** como administrador → Administración del sitio → Notificaciones
3. **Cargar** en `plugins/instalar plugin` dentro de tu entorno moodle
4. **Seguir** el asistente de instalación
5. **Verificar** funcionalidades con la opción de diagnóstico
6. **Listo** ya esta instalado y listo para usarse en tu entorno moodle al agregarlo desde el menu de recursos aparecera la nueva opción

## 📚 Documentación Completa

### 📖 [**Documentación Técnica Completa**](docs/documentacion.md)

La documentación técnica completa incluye:

#### 🏗️ **Arquitectura y Diseño**
- Diagrama de componentes del sistema
- Flujo de datos entre módulos
- Patrones de diseño implementados
- Arquitectura de base de datos

#### 💾 **Base de Datos**
- **26 tablas** completamente documentadas
- Esquemas SQL con comentarios
- Relaciones y claves foráneas
- Índices y optimizaciones

#### 🔧 **APIs y Funciones**
- **50+ funciones** documentadas con ejemplos
- APIs internas para extensión
- Hooks y eventos del sistema
- Interfaces para desarrolladores

#### 📝 **Cada Archivo PHP Explicado**
- **30+ archivos** con propósito detallado
- Variables principales de cada script
- Flujo de ejecución paso a paso
- Ejemplos de uso práctico

#### 🛠️ **Guías de Desarrollo**
- Estándares de código
- Testing y debugging
- Optimización de performance
- Patrones de seguridad

#### 🔍 **Troubleshooting**
- Problemas comunes y soluciones
- Herramientas de diagnóstico
- Scripts de reparación
- Guías de migración

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 7.2+, Moodle APIs
- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **Base de Datos**: MySQL/MariaDB/PostgreSQL
- **Algoritmos**: Felder-Silverman ILS
- **Arquitectura**: MVC, Orientada a Eventos

## 🎯 Casos de Uso

### 📚 **Educación Superior**
- Cursos de ingeniería con diferentes enfoques (teórico vs práctico)
- Programas de ciencias con materiales visuales vs textuales
- Carreras humanísticas con enfoques globales vs secuenciales

### 🎓 **Formación Corporativa**
- Entrenamiento personalizado por perfil profesional
- Programas de capacitación adaptativa
- Certificaciones con rutas flexibles

### 🏫 **Educación Secundaria**
- Materias STEM con enfoques diferenciados
- Programas de recuperación personalizados
- Atención a la diversidad educativa

## 🔬 Validación Científica

Basado en más de **30 años de investigación** en estilos de aprendizaje:
- Modelo validado por **Richard M. Felder** (North Carolina State University)
- Usado en **cientos de instituciones** mundialmente
- **Miles de estudiantes** evaluados con el cuestionario ILS

## 🤝 Contribuir

¡Las contribuciones son bienvenidas!

### 🐛 **Reportar Bugs**
- Usar GitHub Issues
- Incluir versiones de Moodle/PHP
- Proporcionar pasos para reproducir

### 💡 **Sugerir Mejoras**
- Describir beneficio educativo
- Incluir mockups si es posible
- Considerar compatibilidad con Moodle

### 👨‍💻 **Contribuir Código**
- Fork → Develop → Pull Request
- Seguir estándares de Moodle
- Incluir tests unitarios

## 📊 Estadísticas del Proyecto

- **📁 100+ archivos** de código fuente
- **💾 26 tablas** de base de datos
- **🔧 50+ funciones** documentadas
- **📖 1000+ líneas** de documentación técnica
- **🧪 44 preguntas** del cuestionario ILS validado
- **🎯 8 estilos** de aprendizaje soportados

## 📄 Licencia

**GPL v3.0** - Software libre para uso educativo e institucional.

## 🏆 Créditos

**Desarrollado por**: EderPG  
**Basado en**: Modelo Felder-Silverman de Estilos de Aprendizaje  
**Inspirado en**: Investigación de NC State University  
**Agradecimientos**: Comunidad Moodle, usuarios beta y a la ENES campus UNAM Morelia  

---

### 🚀 ¿Listo para revolucionar el aprendizaje en tu institución?

**[📥 Descargar Ahora](https://github.com/EderPG/learningstylesurvey/archive/main.zip)** | **[📖 Ver Documentación](docs/documentacion)** | **[🐛 Reportar Issues](https://github.com/EderPG/learningstylesurvey/issues)**
