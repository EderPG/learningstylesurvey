# Documentación Técnica Completa - Plugin Learning Style Survey para Moodle

---

## 1. Introducción

El plugin **Learning Style Survey** permite adaptar rutas de aprendizaje en Moodle según el estilo detectado de cada estudiante. Es modular, extensible y pensado para facilitar la integración y expansión por parte de desarrolladores. El objetivo es potenciar el aprendizaje personalizado, mejorar la experiencia educativa y permitir la personalización y gestión avanzada por parte de docentes.

---

## 2. Arquitectura General

### Componentes principales

- **Encuesta de estilos de aprendizaje:** Detecta el perfil del usuario mediante preguntas y algoritmos de clasificación.
- **Gestor de rutas de aprendizaje:** Asigna y administra rutas adaptativas, pasos, recursos y evaluaciones por curso.
- **Gestión y visualización del progreso:** Muestra el avance del usuario, bloquea/permite avanzar según resultados y registra el progreso.
- **Gestión docente:** Permite a los profesores crear, editar y organizar rutas, pasos, recursos y evaluaciones, así como consultar el avance de los estudiantes.
- **Recuperación y retroalimentación:** Si el estudiante reprueba una evaluación, se le asignan recursos de recuperación o repite el paso.

#### Diagrama de flujo textual

```text
Estudiante completa encuesta → Sistema detecta estilo → Asigna ruta personalizada → 
Visualiza pasos → Realiza recursos/evaluaciones → Avanza si aprueba → Progreso registrado.
Docente configura rutas/pasos/recursos → Consulta el avance de sus alumnos.
```

---

## 3. Estructura de Archivos y Funcionalidad

| Archivo/Fichero         | Descripción Técnica ----------                                                                                                                               |
|-------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `mod_form.php`          | Formulario de configuración del módulo en Moodle. Permite agregar nombre, descripción y parámetros estándar.                                      |
| `vista_estudiante.php`  | Interfaz principal del estudiante. Obtiene el estilo del usuario, muestra la ruta, los pasos y el progreso. Gestiona avance y validación de pasos.|
| `learningpath.php`      | Interfaz para el docente. Visualiza rutas existentes, permite editar/crear rutas y modificar el orden de los pasos.                               |
| `createsteproute.php`   | Formulario para crear nuevos pasos de una ruta, asociando recursos y evaluaciones. Procesa POST y guarda en BD.                                  |
| `edit_learningpath.php` | Permite editar una ruta existente, modificar pasos y reasignar recursos/evaluaciones. Resetea el progreso si se cambia la ruta.                  |
| `siguiente.php`         | Lógica de avance a siguiente paso. Verifica si el usuario puede avanzar, actualiza el progreso o marca la ruta como completada.                  |
| `responder_quiz.php`    | Renderiza el cuestionario/quiz adaptativo y permite registrar/calificar resultados. Gestiona recuperación si el usuario reprueba.                |
| `verruta.php`           | Vista adicional para mostrar al estudiante los pasos y recursos asignados según su estilo.                                                       |
| `results.php`           | Muestra resultados de encuestas y evaluaciones, útil para análisis docente.                                                                      |
| `guardar_examen.php`    | Procesa y almacena los resultados de exámenes realizados por los estudiantes.                                                                    |
| `guardar_orden.php`     | Permite guardar el orden de los pasos en una ruta, útil para la personalización docente.                                                         |
| `guardar_paso_ruta.php` | Almacena la información de un nuevo paso en la ruta de aprendizaje.                                                                              |
| `organizar_ruta.php`    | Herramienta para organizar y reordenar los pasos de una ruta.                                                                                    |
| `uploadresource.php`    | Permite la subida de recursos didácticos (archivos, imágenes, PDFs, etc.).                                                                      |
| `ver_recurso.php`       | Visualiza recursos asignados a un paso específico.                                                                                               |
| `temas.php`             | Gestión de temas y categorías de recursos, útil para la administración docente.                                                                  |
| `crear_examen.php`      | Permite la creación de exámenes y su asociación a pasos de rutas.                                                                                |
| `lib.php / locallib.php`| Funciones auxiliares y lógica de negocio reutilizable en el plugin.                                                                              |
| `version.php`           | Define la versión del plugin y dependencias requeridas por Moodle.                                                                               |
| `README.md`             | Información básica, instalación y uso rápido del plugin.                                                                                         |
| Carpeta `db`            | Archivos de definición de tablas, accesos y actualizaciones de la base de datos.                                                                |
| Carpeta `lang`          | Archivos de idioma para internacionalización.                                                                                                    |
| Carpeta `pix`           | Imágenes e íconos utilizados en la interfaz.                                                                                                     |
| Carpeta `uploads`       | Archivos subidos por los usuarios (recursos, imágenes, PDFs, etc.).                                                                             |

---

## 4. Modelo de Datos y Tablas

### Principales tablas

- **learningstylesurvey_userstyles:** Guarda el estilo detectado para cada usuario.
- **learningstylesurvey_paths:** Define rutas de aprendizaje por curso.
- **learningpath_steps:** Pasos de cada ruta, con recursos y evaluaciones asociadas.
- **learningstylesurvey_user_progress:** Registra el progreso de cada usuario en su ruta.
- **learningstylesurvey_resources:** Recursos didácticos.
- **learningstylesurvey_quizzes:** Evaluaciones asociadas.

#### Relaciones principales

- Cada usuario tiene un estilo (`userstyles`).
- Cada ruta (`paths`) puede tener múltiples pasos (`steps`).
- Cada paso puede tener múltiples recursos y evaluaciones.
- El progreso del usuario se registra por ruta y paso.
- Los recursos y evaluaciones pueden estar asociados a temas y pasos específicos.

---

## 5. Extensibilidad y Buenas Prácticas

### Puntos de extensión recomendados

- **Nuevos tipos de estilos de aprendizaje:** Modificar la lógica de la encuesta y el algoritmo de asignación.
- **Nuevos recursos o actividades:** Agregar tablas y lógica para asociar otros tipos de recursos (videos, foros, SCORM, etc.).
- **Integración con otras APIs de Moodle:** Utilizar hooks y eventos de Moodle para expandir notificaciones, reportes, etc.
- **Mejoras en la gestión docente:** Añadir dashboards, reportes avanzados, personalización de rutas por grupo, etc.
- **Internacionalización:** Agregar nuevos idiomas en la carpeta `lang`.
- **Seguridad y permisos:** Revisar y mejorar el uso de `require_capability()` y roles.

### Recomendaciones de desarrollo

- Mantener compatibilidad con las APIs estándar de Moodle.
- Utilizar funciones de acceso a datos de Moodle para evitar SQL directos.
- Separar la lógica de negocio de la presentación (MVC básico).
- Documentar cada función y archivo nuevo.
- Realizar pruebas unitarias y de integración.
- Usar control de versiones (Git) y ramas para desarrollo colaborativo.
- Seguir las guías de estilo de código de Moodle.

---

## 6. Ejemplo de Expansión: Agregar un Nuevo Tipo de Recurso

1. Crear nueva tabla `learningstylesurvey_videos` con campos relevantes.
2. Modificar `createsteproute.php` y `edit_learningpath.php` para permitir seleccionar y asociar videos a pasos.
3. Actualizar la lógica en `vista_estudiante.php` para renderizar videos según el paso.
4. Documentar el nuevo componente y agregar test unitarios.
5. Añadir soporte en la interfaz docente para gestionar videos.

---

## 7. Integración con Moodle

- **Hooks y APIs:** Uso de funciones estándar como `require_login()`, `context_course::instance()`, `$DB->get_record()`, `$OUTPUT->header()`, etc.
- **Formularios:** `mod_form.php` utiliza la clase `moodleform_mod`.
- **Contextos y permisos:** Uso de `require_capability()` para validar roles y permisos.
- **Internacionalización:** Archivos en `lang` para traducción de la interfaz.
- **Gestión de archivos:** Recursos subidos se almacenan en la carpeta `uploads`.

---

## 8. Ciclo de vida de usuario

### Estudiante

- Ingresa al curso, responde la encuesta.
- Se le asigna una ruta personalizada según su estilo.
- Avanza paso a paso (recursos, evaluaciones).
- Si reprueba, recibe recursos de recuperación o repite el paso.
- Finaliza la ruta y puede consultar su progreso y resultados.
- Puede acceder a recursos adicionales según su avance y estilo.

### Docente

- Configura rutas, pasos, recursos y evaluaciones.
- Consulta el avance de sus estudiantes.
- Modifica rutas y pasos según necesidades.
- Accede a reportes y dashboards.
- Gestiona temas, recursos y exámenes.
- Organiza y personaliza la experiencia de aprendizaje adaptativo.

---

## 9. Troubleshooting y Preguntas Frecuentes

- **No se asigna ruta al estudiante:** Verificar que el usuario haya completado la encuesta y que existan rutas definidas para el curso.
- **No avanza al siguiente paso:** Revisar la configuración de evaluaciones y los redirects (`passredirect`, `failredirect`).
- **Error al crear recurso/evaluación:** Comprobar la estructura de las tablas y permisos del usuario.
- **Problemas de visualización:** Revisar los archivos en `pix` y la configuración de recursos subidos.
- **Errores de permisos:** Validar el uso de `require_capability()` y los roles asignados en Moodle.

---

## 10. Anexos y Ejemplos

### Fragmento de avance de paso (vista_estudiante.php)

```php
if ($result->score >= 70 && $currentstep->passredirect) {
    $progress->current_stepid = $currentstep->passredirect;
    $DB->update_record('learningstylesurvey_user_progress', $progress);
    redirect(new moodle_url('/mod/learningstylesurvey/vista_estudiante.php', ['courseid'=>$courseid,'pathid'=>$pathid]));
}
```

### SQL ejemplo: Obtener pasos de una ruta

```sql
SELECT * FROM {learningpath_steps}
WHERE pathid = ?
ORDER BY stepnumber ASC;
```

### Ejemplo de definición de recurso en PHP

```php
$resource = [
    'id' => 1,
    'tema_id' => 2,
    'name' => 'Video Introductorio',
    'type' => 'video',
    'url' => 'https://...'
];
$DB->insert_record('learningstylesurvey_resources', $resource);
```

### Ejemplo de formulario en mod_form.php

```php
$mform->addElement('text', 'name', get_string('learningstylesurveyname', 'learningstylesurvey'), array('size' => '64'));
$mform->setType('name', PARAM_TEXT);
$mform->addRule('name', null, 'required', null, 'client');
$this->standard_intro_elements();
$this->standard_coursemodule_elements();
$this->add_action_buttons();
```

---

## 11. 🔄 Revisión de Portabilidad - Guía de Instalación Multiplataforma

### ✅ Problemas de Portabilidad Corregidos

#### 1. **Enlaces Hardcodeados a URLs Relativas**

**Problema**: Muchos archivos usaban enlaces directos como `href="view.php?id=123"` que fallarían en otros entornos.

**Solución**: Reemplazados por `moodle_url` estándar:

```php
// ❌ Antes (hardcoded)
echo "<a href='view.php?id={$cmid}'>Regresar</a>";

// ✅ Después (portable)
$viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
echo "<a href='" . $viewurl->out() . "'>Regresar</a>";
```

**Archivos Corregidos**:

- ✅ `manage_quiz.php` - 2 enlaces corregidos
- ✅ `uploadresource.php` - 1 enlace corregido  
- ✅ `crear_examen.php` - 1 enlace corregido
- ✅ `responder_quiz.php` - 1 enlace corregido
- ✅ `view.php` - 1 enlace corregido
- ✅ `verificar_funcionalidades.php` - 7 enlaces corregidos
- ✅ `ver_recurso.php` - 1 enlace corregido

#### 2. **Obtención Correcta de Course Module ID (cmid)**

**Problema**: Algunos archivos no obtenían correctamente el `cmid` del módulo.

**Solución**: Implementado método estándar:

```php
// Método estándar para obtener cmid
$modinfo = get_fast_modinfo($courseid);
$cmid = null;
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->modname === 'learningstylesurvey') {
        $cmid = $cm->id;
        break;
    }
}
```

**Archivos Verificados**:

- ✅ `uploadresource.php` - Ya usa método correcto
- ✅ `vista_estudiante.php` - Ya usa método correcto  
- ✅ `organizar_ruta.php` - Ya usa método correcto
- ✅ `manage_quiz.php` - Ya usa método correcto

#### 3. **Navegación Estándar entre Páginas**

**Problema**: Enlaces que no seguían las convenciones de Moodle para navegación.

**Solución**: Todos los enlaces ahora usan:

- `moodle_url` para construcción de URLs
- Parámetros apropiados (`courseid`, `id`, etc.)
- Rutas absolutas desde la raíz de Moodle

### 🔍 Archivos con Portabilidad Verificada

#### Archivos Principales ✅

- `view.php` - Menú principal del módulo
- `vista_estudiante.php` - Interfaz de estudiante
- `responder_quiz.php` - Sistema de evaluaciones
- `organizar_ruta.php` - Organizador de rutas

#### Archivos de Gestión ✅

- `manage_quiz.php` - Gestión de exámenes
- `uploadresource.php` - Subida de recursos
- `crear_examen.php` - Creación de exámenes
- `verificar_funcionalidades.php` - Herramientas de verificación

#### Archivos de Navegación ✅

- `ver_recurso.php` - Visualización de recursos
- Todos los enlaces de retorno funcionan correctamente

### 🚀 Beneficios de la Portabilidad

1. **Instalación en Cualquier Entorno**: El módulo funcionará correctamente en diferentes instancias de Moodle
2. **URLs Dinámicas**: No depende de IDs específicos del entorno de desarrollo
3. **Compatibilidad con Subdirectorios**: Funciona si Moodle está instalado en subdirectorios
4. **Compatibilidad con SSL/HTTPS**: Las URLs se adaptan automáticamente al protocolo
5. **Multi-tenant**: Funciona en entornos con múltiples sitios Moodle

### 🔧 Método de Navegación Estándar Implementado

```php
// Patrón estándar para navegar de vuelta al módulo
$modinfo = get_fast_modinfo($courseid);
$cmid = null;
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->modname === 'learningstylesurvey') {
        $cmid = $cm->id;
        break;
    }
}

if ($cmid) {
    $viewurl = new moodle_url('/mod/learningstylesurvey/view.php', ['id' => $cmid]);
    echo html_writer::link($viewurl, 'Regresar al menú', ['class' => 'btn btn-secondary']);
} else {
    $courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
    echo html_writer::link($courseurl, 'Regresar al curso', ['class' => 'btn btn-secondary']);
}
```

### ✅ Estado Final de Portabilidad

- **14 enlaces corregidos** en 7 archivos críticos
- **0 errores de sintaxis** detectados
- **Navegación estándar** implementada en todos los archivos
- **Portabilidad completa** verificada

El módulo está ahora listo para instalación en cualquier entorno Moodle sin dependencias de IDs específicos.

---

## 12. Licencia y soporte

Este plugin se distribuye bajo GPL v3.  
Para soporte, mejorar funcionalidades o reportar errores, utiliza el sistema de issues en [GitHub](https://github.com/EderPG/learningstylesurvey).

---

## 13. Recursos útiles

- [Documentación oficial de Moodle para desarrolladores](https://moodledev.io/)
- [API Database Moodle](https://moodledev.io/docs/apis/core/dml)
- [Ejemplo de plugin modular](https://moodledev.io/docs/apis/plugins)
- [Guía de estilos de código Moodle](https://moodledev.io/docs/guides/codingstyle)
- [Foro de la comunidad Moodle](https://moodle.org/mod/forum/)

---

## 14. Roadmap y mejoras futuras

- Integración con analíticas de aprendizaje.
- Soporte para nuevos estilos y algoritmos de clasificación.
- Expansión de recursos multimedia y actividades.
- Mejoras en la visualización y reportes docentes.
- Internacionalización completa.
- Pruebas automatizadas y cobertura de código.

---

## 15. Observaciones Críticas y Lecciones Aprendidas

### 🚨 **Problemas Comunes y Soluciones**

Esta sección documenta problemas críticos encontrados durante el desarrollo y mantenimiento del sistema, así como sus soluciones implementadas para evitar futuros errores.

#### **15.1. Sistema de Evaluación de Exámenes**

**Problema identificado:**
- El sistema de evaluación de exámenes no funcionaba correctamente
- Todas las respuestas se marcaban como incorrectas independientemente de la respuesta seleccionada
- Múltiples intentos no se registraban correctamente

**Causa raíz:**
1. **Inconsistencia en índices**: El sistema usa índices empezando desde 0 (0, 1, 2, 3) pero la lógica de evaluación calculaba desde 1
2. **Orden de opciones**: Las opciones no se ordenaban consistentemente entre creación, edición y evaluación
3. **Actualización vs nuevos registros**: Los intentos de examen actualizaban el mismo registro en lugar de crear nuevos

**Solución implementada:**
```php
// ✅ CORRECTO: Índices consistentes empezando desde 0
$optionIndex = 0; // No desde 1
foreach ($options as $opt) {
    if ($opt->id == $userOptionId) {
        $selectedIndex = $optionIndex;
        break;
    }
    $optionIndex++;
}

// ✅ CORRECTO: Ordenar opciones consistentemente
$options = $DB->get_records('learningstylesurvey_options', 
    ['questionid' => $q->id], 'id ASC');

// ✅ CORRECTO: Cada intento es un nuevo registro
$DB->insert_record('learningstylesurvey_quiz_results', $record);
// NO: $DB->update_record() que mantiene el mismo registro
```

**Archivos afectados:**
- `responder_quiz.php`: Lógica de evaluación
- `manage_quiz.php`: Edición de exámenes  
- `crear_examen.php`: Creación de exámenes
- `guardar_examen.php`: Guardado de exámenes

#### **15.2. Estructura de Base de Datos y Tablas**

**Problema identificado:**
- Errores por tablas inexistentes (`mdl_learningstylesurvey_quiz` vs `mdl_learningstylesurvey_quizzes`)
- Confusión entre tabla de install.xml vs upgrade.php
- Campos inexistentes (`temaid` vs `tema`)

**Tablas REALES que existen:**

```sql
-- === DESDE INSTALL.XML (instalación inicial) ===

-- Tabla principal del módulo
learningstylesurvey                    -- Instancia principal del módulo

-- Gestión de estilos de aprendizaje
learningstylesurvey_results            -- Estilo más fuerte del alumno (OBSOLETA)
learningstylesurvey_userstyles         -- Estilo asignado a cada usuario (ACTUAL)
learningstylesurvey_responses          -- Respuestas individuales de la encuesta

-- Sistema de rutas de aprendizaje
learningstylesurvey_learningpath       -- Ruta de aprendizaje (sistema original)
learningstylesurvey_paths              -- Rutas personalizadas (sistema nuevo)
learningpath_steps                     -- Pasos de la ruta (sistema original activo)
learningstylesurvey_user_progress      -- Progreso de usuarios en rutas

-- Gestión de recursos
learningstylesurvey_resources          -- Archivos subidos (campo: tema, NO temaid)
learningstylesurvey_inforoute          -- Pasos informativos de una ruta
learningstylesurvey_path_files         -- Archivos por ruta

-- Sistema de evaluaciones/quizzes
learningstylesurvey_quizzes            -- Cuestionarios (NO: learningstylesurvey_quiz)
learningstylesurvey_questions          -- Preguntas de quizzes
learningstylesurvey_options            -- Opciones por pregunta
learningstylesurvey_quiz_results       -- Resultados de quizzes
learningstylesurvey_path_evaluations   -- Evaluaciones por ruta

-- Gestión de temas
learningstylesurvey_temas              -- Temas registrados por curso

-- === DESDE UPGRADE.PHP (actualizaciones) ===
learningstylesurvey_path_temas         -- Relación rutas-temas (solo por upgrade)
learningstylesurvey_path_evaluations   -- También definida en upgrade scripts
```

**Campos críticos por tabla:**

```sql
-- learningstylesurvey_resources
- id, courseid, name, filename, style, tema, recoveryquizid
- ⚠️ Campo es 'tema' (NO 'temaid')
- ⚠️ 'userid' es del profesor que creó el recurso, NO del estudiante

-- learningstylesurvey_quizzes  
- id, name, userid, timecreated, courseid, orden
- ⚠️ Nombre correcto es 'quizzes' (NO 'quiz')

-- learningstylesurvey_questions
- id, quizid, questiontext, correctanswer
- ⚠️ 'correctanswer' puede ser índice numérico (0,1,2,3) o texto

-- learningstylesurvey_options
- id, questionid, optiontext
- ⚠️ Ordenar siempre por 'id ASC' para consistencia

-- learningpath_steps (SISTEMA PRINCIPAL ACTIVO)
- id, pathid, stepnumber, resourceid, istest, passredirect, failredirect
- ⚠️ Esta tabla sigue siendo la principal para navegación

-- learningstylesurvey_userstyles
- id, userid, style, timecreated  
- ⚠️ Usar ORDER BY timecreated DESC LIMIT 1 para obtener estilo más reciente
```

**Lección aprendida:**
- **SIEMPRE revisar install.xml Y upgrade.php** antes de asumir estructura de tablas
- **Verificar nombres exactos** de tablas y campos
- **No asumir** que tablas mencionadas en código existen sin verificar

#### **15.3. Sistema de Rutas Adaptativas vs Sistema Original**

**Problema identificado:**
- Mezcla entre sistema original (`learningpath_steps`) y nuevo sistema (`learningstylesurvey_path_temas`)
- Consultas que fallaban por usar tablas incorrectas

**Sistema híbrido actual:**
```php
// Sistema ORIGINAL (funcional)
learningpath_steps -> Para navegación principal
learningstylesurvey_resources -> Para recursos filtrados por estilo

// Sistema NUEVO (de upgrade scripts)  
learningstylesurvey_path_temas -> Para gestión de temas en rutas
learningstylesurvey_path_evaluations -> Para evaluaciones en rutas
```

**Recomendación:**
- Mantener compatibilidad con sistema original mientras se migra gradualmente
- No asumir que nuevas tablas reemplazan completamente las originales

#### **15.4. Filtrado de Recursos por Estilo de Aprendizaje**

**Problema identificado:**
- Recursos no se mostraban aunque existían en la base de datos
- Case sensitivity en comparación de estilos
- Filtrado incorrecto por usuario

**Solución:**
```php
// ✅ CORRECTO: Normalización de estilos (case-insensitive)
$style = strtolower(trim($userstyle->style));

// ✅ CORRECTO: Filtrado correcto (SIN userid del estudiante)
$recursos = $DB->get_records('learningstylesurvey_resources', [
    'tema' => $tema_id,
    'style' => $style,
    'courseid' => $courseid
    // NO: 'userid' => $USER->id (es del profesor que creó el recurso)
]);
```

#### **15.5. Sistema de Saltos de Exámenes y Refuerzo**

**Problema identificado:**
- Estudiantes enviados incorrectamente a temas de refuerzo después de aprobar exámenes
- Sistema consideraba cualquier examen reprobado del curso, no específico de la ruta

**Solución:**
```php
// ✅ CORRECTO: Solo considerar exámenes de la ruta específica
$lastquiz = $DB->get_record_sql("
    SELECT qr.*, s.failredirect 
    FROM {learningstylesurvey_quiz_results} qr
    JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
    WHERE qr.userid = ? AND qr.courseid = ? AND s.pathid = ?
    ORDER BY qr.timecompleted DESC 
    LIMIT 1
", [$USER->id, $courseid, $pathid]);
```

### 🔧 **Mejores Prácticas Implementadas**

#### **A. Verificación de Estructura de BD**
```bash
# Antes de modificar código, SIEMPRE verificar:
1. Revisar db/install.xml para tablas base
2. Revisar db/upgrade.php para nuevas tablas  
3. Verificar nombres exactos de campos
4. Probar consultas en phpmyadmin/CLI antes de implementar
```

#### **B. Consistencia en Índices**
```php
// Mantener CONSISTENCIA en todo el sistema:
// Creación: índices 0, 1, 2, 3
// Edición: índices 0, 1, 2, 3  
// Evaluación: índices 0, 1, 2, 3
// NO mezclar sistemas de índices
```

#### **C. Debug y Logging**
```php
// Implementar debug temporal para diagnóstico:
error_log("Question {$q->id}: Selected='$selectedText' vs Correct='{$q->correctanswer}'");

// Activable con parámetros URL:
$debug_info = optional_param('debug', 0, PARAM_INT);
if ($debug_info) {
    // Mostrar información de diagnóstico
}
```

#### **D. Gestión de Intentos de Examen**
```php
// ✅ CORRECTO: Cada intento = nuevo registro
$DB->insert_record('learningstylesurvey_quiz_results', $record);

// ❌ INCORRECTO: Actualizar mismo registro
// $DB->update_record('learningstylesurvey_quiz_results', $record);
```

### 📝 **Checklist para Futuras Modificaciones**

Antes de modificar el sistema de evaluación:
- [ ] ✅ Verificar estructura de tablas en install.xml y upgrade.php
- [ ] ✅ Mantener consistencia de índices (empezar desde 0)
- [ ] ✅ Ordenar opciones consistentemente (`ORDER BY id ASC`)
- [ ] ✅ Probar con datos reales antes de deployment
- [ ] ✅ Implementar debug temporal durante desarrollo
- [ ] ✅ Verificar que filtros de estilo usen normalización
- [ ] ✅ Confirmar que saltos de examen filtren por ruta específica

---

## 16. Documentación Detallada de Variables y Funciones

### 16.1. Variables Principales del Sistema

#### **Variables de Configuración Global**

```php
// === CONFIGURACIÓN PRINCIPAL ===
$CFG->dataroot          // Directorio raíz de datos de Moodle
$CFG->dirroot           // Directorio raíz de instalación de Moodle

// === VARIABLES DE SESIÓN ===
$USER->id               // ID del usuario actual en sesión
$USER->firstname        // Nombre del usuario
$USER->lastname         // Apellido del usuario

// === VARIABLES DE CURSO ===
$courseid               // ID del curso actual (tipo: int)
$course->id             // Objeto curso completo con todos sus campos
$course->fullname       // Nombre completo del curso
$course->shortname      // Nombre corto del curso

// === VARIABLES DE MÓDULO ===
$cmid                   // Course Module ID - Identificador del módulo en el curso
$cm->id                 // ID del módulo de curso
$cm->instance           // ID de la instancia específica del plugin
$cm->course             // ID del curso al que pertenece el módulo
$cm->modname            // Nombre del módulo ('learningstylesurvey')

// === VARIABLES DE CONTEXTO ===
$context                // Contexto de Moodle para permisos
$PAGE                   // Objeto página global de Moodle
$OUTPUT                 // Renderer de salida de Moodle
$DB                     // Objeto de base de datos global
```

#### **Variables de Estilos de Aprendizaje**

```php
// === ESTILOS RECONOCIDOS POR EL SISTEMA ===
$learning_styles = [
    'activo',           // Estudiante que aprende haciendo
    'reflexivo',        // Estudiante que aprende pensando
    'sensorial',        // Estudiante orientado a hechos
    'intuitivo',        // Estudiante orientado a teorías
    'visual',           // Estudiante que aprende viendo
    'verbal',           // Estudiante que aprende escuchando
    'secuencial',       // Estudiante paso a paso
    'global'            // Estudiante de panorama general
];

// === VARIABLES DE DETECCIÓN DE ESTILO ===
$stylecounts            // Array con conteo de respuestas por estilo
$strongest_style        // Estilo dominante detectado
$user_style            // Estilo asignado al usuario (string)
```

#### **Variables de Rutas de Aprendizaje**

```php
// === RUTA PRINCIPAL ===
$pathid                 // ID de la ruta de aprendizaje (int)
$path_name             // Nombre de la ruta personalizada
$path_steps            // Array de pasos de la ruta
$current_step          // Paso actual del estudiante
$current_stepid        // ID del paso actual

// === PROGRESO DEL USUARIO ===
$progress              // Objeto progreso del usuario
$progress->userid      // ID del usuario
$progress->pathid      // ID de la ruta
$progress->current_stepid  // ID del paso actual
$progress->status      // Estado: 'inprogress', 'completed', 'blocked'
$progress->timemodified // Última modificación

// === PASOS DE RUTA ===
$step->id              // ID único del paso
$step->pathid          // ID de la ruta padre
$step->stepnumber      // Número de orden del paso
$step->resourceid      // ID del recurso asociado
$step->istest          // 1 si es examen, 0 si es recurso
$step->passredirect    // ID tema destino si aprueba
$step->failredirect    // ID tema destino si reprueba
```

#### **Variables de Recursos**

```php
// === RECURSO DIDÁCTICO ===
$resource->id          // ID único del recurso
$resource->courseid    // ID del curso
$resource->name        // Nombre del recurso
$resource->filename    // Nombre del archivo subido
$resource->style       // Estilo de aprendizaje al que pertenece
$resource->tema        // ID del tema asociado (NO temaid)
$resource->userid      // ID del profesor que creó el recurso
$resource->timecreated // Fecha de creación
$resource->recoveryquizid // Quiz de recuperación (opcional)

// === TEMA EDUCATIVO ===
$tema->id              // ID único del tema
$tema->courseid        // ID del curso
$tema->tema            // Descripción del tema (texto)
$tema->timecreated     // Fecha de creación
$tema->userid          // ID del profesor que creó el tema
```

#### **Variables de Evaluaciones/Quizzes**

```php
// === QUIZ/EXAMEN ===
$quiz->id              // ID único del quiz
$quiz->name            // Nombre del examen
$quiz->userid          // ID del profesor que lo creó
$quiz->courseid        // ID del curso
$quiz->timecreated     // Fecha de creación
$quiz->orden           // Orden en la lista

// === PREGUNTA ===
$question->id          // ID único de la pregunta
$question->quizid      // ID del quiz padre
$question->questiontext // Texto de la pregunta
$question->correctanswer // Respuesta correcta (índice o texto)

// === OPCIÓN DE RESPUESTA ===
$option->id            // ID único de la opción
$option->questionid    // ID de la pregunta padre
$option->optiontext    // Texto de la opción

// === RESULTADO DE QUIZ ===
$result->id            // ID único del resultado
$result->userid        // ID del estudiante
$result->quizid        // ID del quiz respondido
$result->score         // Puntuación obtenida (0-100)
$result->courseid      // ID del curso
$result->timecompleted // Fecha de finalización
$result->timemodified  // Última modificación
```

### 16.2. Funciones Principales del Sistema

#### **Funciones de Gestión de Estilos**

```php
/**
 * Guarda el estilo más fuerte detectado para un usuario
 * @param int $userid ID del usuario
 * @param string $style Estilo detectado
 */
function learningstylesurvey_save_strongest_style($userid, $style) {
    global $DB;
    
    // Buscar si ya existe un registro
    $record = $DB->get_record('learningstylesurvey_userstyles', 
        ['userid' => $userid], '*', IGNORE_MULTIPLE);
    
    if ($record) {
        // Actualizar registro existente
        $record->style = $style;
        $record->timecreated = time();
        $DB->update_record('learningstylesurvey_userstyles', $record);
    } else {
        // Crear nuevo registro
        $new_record = new stdClass();
        $new_record->userid = $userid;
        $new_record->style = $style;
        $new_record->timecreated = time();
        $DB->insert_record('learningstylesurvey_userstyles', $new_record);
    }
}

/**
 * Obtiene el estilo de aprendizaje de un usuario
 * @param int $userid ID del usuario
 * @return string|false Estilo del usuario o false si no existe
 */
function learningstylesurvey_get_user_style($userid) {
    global $DB;
    
    $record = $DB->get_record('learningstylesurvey_userstyles', 
        ['userid' => $userid], '*', IGNORE_MULTIPLE);
    
    return $record ? strtolower(trim($record->style)) : false;
}
```

#### **Funciones de Gestión de Rutas**

```php
/**
 * Obtiene la ruta asignada para un curso
 * @param int $courseid ID del curso
 * @return object|false Objeto ruta o false si no existe
 */
function learningstylesurvey_get_course_path($courseid) {
    global $DB;
    
    return $DB->get_record('learningstylesurvey_paths', 
        ['courseid' => $courseid], '*', IGNORE_MULTIPLE);
}

/**
 * Obtiene los pasos de una ruta ordenados
 * @param int $pathid ID de la ruta
 * @return array Array de objetos paso
 */
function learningstylesurvey_get_path_steps($pathid) {
    global $DB;
    
    return $DB->get_records('learningpath_steps', 
        ['pathid' => $pathid], 'stepnumber ASC');
}

/**
 * Obtiene el progreso de un usuario en una ruta
 * @param int $userid ID del usuario
 * @param int $pathid ID de la ruta
 * @return object|false Objeto progreso o false si no existe
 */
function learningstylesurvey_get_user_progress($userid, $pathid) {
    global $DB;
    
    return $DB->get_record('learningstylesurvey_user_progress', 
        ['userid' => $userid, 'pathid' => $pathid]);
}
```

#### **Funciones de Gestión de Recursos**

```php
/**
 * Obtiene recursos filtrados por estilo y tema
 * @param int $courseid ID del curso
 * @param string $style Estilo de aprendizaje
 * @param int $tema_id ID del tema
 * @return array Array de recursos
 */
function learningstylesurvey_get_filtered_resources($courseid, $style, $tema_id) {
    global $DB;
    
    $params = [
        'courseid' => $courseid,
        'style' => strtolower(trim($style)),
        'tema' => $tema_id
    ];
    
    return $DB->get_records('learningstylesurvey_resources', $params);
}

/**
 * Asegura que el directorio de subida existe
 * @param int $courseid ID del curso
 * @return string Ruta del directorio
 */
function learningstylesurvey_ensure_upload_directory($courseid) {
    global $CFG;
    
    $upload_dir = $CFG->dataroot . '/learningstylesurvey/' . $courseid . '/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new moodle_exception('Cannot create upload directory');
        }
    }
    
    return $upload_dir;
}
```

#### **Funciones de Evaluación**

```php
/**
 * Calcula la puntuación de un quiz
 * @param int $quizid ID del quiz
 * @param array $user_answers Respuestas del usuario
 * @return array ['score' => puntuación, 'details' => detalles]
 */
function learningstylesurvey_calculate_quiz_score($quizid, $user_answers) {
    global $DB;
    
    $questions = $DB->get_records('learningstylesurvey_questions', 
        ['quizid' => $quizid]);
    
    $total_questions = count($questions);
    $correct_answers = 0;
    $details = [];
    
    foreach ($questions as $question) {
        $user_answer = isset($user_answers[$question->id]) ? 
            $user_answers[$question->id] : null;
        
        $is_correct = false;
        
        // Verificar si la respuesta es correcta
        if ($user_answer !== null) {
            if (is_numeric($question->correctanswer)) {
                // Respuesta basada en índice
                $options = $DB->get_records('learningstylesurvey_options', 
                    ['questionid' => $question->id], 'id ASC');
                $options_array = array_values($options);
                $correct_option_id = isset($options_array[$question->correctanswer]) ? 
                    $options_array[$question->correctanswer]->id : null;
                
                $is_correct = ($user_answer == $correct_option_id);
            } else {
                // Respuesta basada en texto
                $selected_option = $DB->get_record('learningstylesurvey_options', 
                    ['id' => $user_answer]);
                $is_correct = $selected_option && 
                    (strtolower(trim($selected_option->optiontext)) === 
                     strtolower(trim($question->correctanswer)));
            }
        }
        
        if ($is_correct) {
            $correct_answers++;
        }
        
        $details[] = [
            'question_id' => $question->id,
            'user_answer' => $user_answer,
            'correct' => $is_correct
        ];
    }
    
    $score = $total_questions > 0 ? 
        round(($correct_answers / $total_questions) * 100) : 0;
    
    return [
        'score' => $score,
        'correct' => $correct_answers,
        'total' => $total_questions,
        'details' => $details
    ];
}
```

### 16.3. Algoritmo de Detección de Estilos de Aprendizaje

El sistema utiliza el cuestionario ILS (Index of Learning Styles) de Felder-Silverman con 44 preguntas que evalúan 4 dimensiones:

```php
/**
 * Calcula los estilos de aprendizaje basado en respuestas ILS
 * @param array $responses Respuestas del usuario (1-44)
 * @return array Conteos por cada estilo
 */
function learningstylesurvey_calculate_learning_styles($responses) {
    // Inicializar contadores
    $stylecounts = [
        'Activo' => 0, 'Reflexivo' => 0,      // Dimensión Activo/Reflexivo
        'Sensorial' => 0, 'Intuitivo' => 0,   // Dimensión Sensorial/Intuitivo  
        'Visual' => 0, 'Verbal' => 0,         // Dimensión Visual/Verbal
        'Secuencial' => 0, 'Global' => 0      // Dimensión Secuencial/Global
    ];
    
    // Mapeo de preguntas a estilos (respuesta 0 = primer estilo, respuesta 1 = segundo estilo)
    $question_mapping = [
        // Dimensión Activo (0) vs Reflexivo (1)
        1 => ['Activo', 'Reflexivo'],
        5 => ['Reflexivo', 'Activo'],
        9 => ['Activo', 'Reflexivo'],
        13 => ['Activo', 'Reflexivo'],
        17 => ['Reflexivo', 'Activo'],
        21 => ['Activo', 'Reflexivo'],
        25 => ['Reflexivo', 'Activo'],
        29 => ['Activo', 'Reflexivo'],
        33 => ['Reflexivo', 'Activo'],
        37 => ['Activo', 'Reflexivo'],
        41 => ['Activo', 'Reflexivo'],
        
        // Dimensión Sensorial (0) vs Intuitivo (1)
        2 => ['Sensorial', 'Intuitivo'],
        6 => ['Sensorial', 'Intuitivo'],
        10 => ['Intuitivo', 'Sensorial'],
        14 => ['Sensorial', 'Intuitivo'],
        18 => ['Sensorial', 'Intuitivo'],
        22 => ['Intuitivo', 'Sensorial'],
        26 => ['Sensorial', 'Intuitivo'],
        30 => ['Sensorial', 'Intuitivo'],
        34 => ['Intuitivo', 'Sensorial'],
        38 => ['Sensorial', 'Intuitivo'],
        42 => ['Intuitivo', 'Sensorial'],
        
        // Dimensión Visual (0) vs Verbal (1)
        3 => ['Visual', 'Verbal'],
        7 => ['Visual', 'Verbal'],
        11 => ['Visual', 'Verbal'],
        15 => ['Verbal', 'Visual'],
        19 => ['Visual', 'Verbal'],
        23 => ['Visual', 'Verbal'],
        27 => ['Verbal', 'Visual'],
        31 => ['Visual', 'Verbal'],
        35 => ['Verbal', 'Visual'],
        39 => ['Verbal', 'Visual'],
        43 => ['Visual', 'Verbal'],
        
        // Dimensión Secuencial (0) vs Global (1)
        4 => ['Secuencial', 'Global'],
        8 => ['Secuencial', 'Global'],
        12 => ['Global', 'Secuencial'],
        16 => ['Secuencial', 'Global'],
        20 => ['Global', 'Secuencial'],
        24 => ['Secuencial', 'Global'],
        28 => ['Global', 'Secuencial'],
        32 => ['Secuencial', 'Global'],
        36 => ['Global', 'Secuencial'],
        40 => ['Secuencial', 'Global'],
        44 => ['Secuencial', 'Global']
    ];
    
    // Procesar cada respuesta
    foreach ($responses as $question_num => $response) {
        if (isset($question_mapping[$question_num])) {
            $styles = $question_mapping[$question_num];
            $selected_style = $styles[$response]; // response es 0 o 1
            $stylecounts[$selected_style]++;
        }
    }
    
    return $stylecounts;
}

/**
 * Determina el estilo más fuerte
 * @param array $stylecounts Conteos por estilo
 * @return string Estilo dominante
 */
function learningstylesurvey_get_strongest_style($stylecounts) {
    // Calcular diferencias por dimensión
    $activo_reflexivo = $stylecounts['Activo'] - $stylecounts['Reflexivo'];
    $sensorial_intuitivo = $stylecounts['Sensorial'] - $stylecounts['Intuitivo'];
    $visual_verbal = $stylecounts['Visual'] - $stylecounts['Verbal'];
    $secuencial_global = $stylecounts['Secuencial'] - $stylecounts['Global'];
    
    // Encontrar la dimensión con mayor diferencia absoluta
    $differences = [
        'Activo/Reflexivo' => abs($activo_reflexivo),
        'Sensorial/Intuitivo' => abs($sensorial_intuitivo),
        'Visual/Verbal' => abs($visual_verbal),
        'Secuencial/Global' => abs($secuencial_global)
    ];
    
    $max_difference = max($differences);
    $dominant_dimension = array_search($max_difference, $differences);
    
    // Determinar el estilo específico
    switch ($dominant_dimension) {
        case 'Activo/Reflexivo':
            return ($activo_reflexivo > 0) ? 'Activo' : 'Reflexivo';
        case 'Sensorial/Intuitivo':
            return ($sensorial_intuitivo > 0) ? 'Sensorial' : 'Intuitivo';
        case 'Visual/Verbal':
            return ($visual_verbal > 0) ? 'Visual' : 'Verbal';
        case 'Secuencial/Global':
            return ($secuencial_global > 0) ? 'Secuencial' : 'Global';
        default:
            return 'Visual'; // Por defecto
    }
}
```

### 16.4. Sistema de Navegación Adaptativa

```php
/**
 * Determina el siguiente paso basado en el resultado de un examen
 * @param object $current_step Paso actual
 * @param int $score Puntuación obtenida
 * @param int $passing_score Puntuación mínima para aprobar (por defecto 70)
 * @return int|null ID del siguiente paso o null si no hay redirección
 */
function learningstylesurvey_get_next_step($current_step, $score, $passing_score = 70) {
    if ($current_step->istest) {
        if ($score >= $passing_score) {
            // Aprobó: ir al tema indicado en passredirect
            return $current_step->passredirect;
        } else {
            // Reprobó: ir al tema de refuerzo en failredirect
            return $current_step->failredirect;
        }
    }
    
    // Si no es examen, seguir secuencia normal
    return null;
}

/**
 * Actualiza el progreso del usuario
 * @param int $userid ID del usuario
 * @param int $pathid ID de la ruta
 * @param int $new_stepid ID del nuevo paso
 * @return bool Éxito de la operación
 */
function learningstylesurvey_update_user_progress($userid, $pathid, $new_stepid) {
    global $DB;
    
    $progress = $DB->get_record('learningstylesurvey_user_progress', 
        ['userid' => $userid, 'pathid' => $pathid]);
    
    if ($progress) {
        $progress->current_stepid = $new_stepid;
        $progress->timemodified = time();
        return $DB->update_record('learningstylesurvey_user_progress', $progress);
    } else {
        $new_progress = new stdClass();
        $new_progress->userid = $userid;
        $new_progress->pathid = $pathid;
        $new_progress->current_stepid = $new_stepid;
        $new_progress->status = 'inprogress';
        $new_progress->timemodified = time();
        return $DB->insert_record('learningstylesurvey_user_progress', $new_progress);
    }
}
```

---

## 17. Documentación Completa de Base de Datos

### 17.1. Esquema Completo de Tablas

#### **Tabla: `learningstylesurvey` (Instancia Principal)**

```sql
CREATE TABLE mdl_learningstylesurvey (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único de la instancia
    name varchar(255) NOT NULL,               -- Nombre del módulo
    intro longtext,                           -- Descripción/introducción
    introformat smallint(4) NOT NULL DEFAULT 1, -- Formato del texto intro
    timecreated bigint(10) NOT NULL DEFAULT 0,  -- Fecha de creación
    timemodified bigint(10) DEFAULT 0,          -- Fecha de modificación
    PRIMARY KEY (id)
);
```

**Propósito**: Tabla principal que define cada instancia del módulo en un curso.

**Campos importantes**:
- `name`: Título mostrado en el curso
- `intro`: Descripción que ve el estudiante
- `introformat`: 0=texto plano, 1=HTML, 2=Markdown

#### **Tabla: `learningstylesurvey_userstyles` (Estilos de Usuario)**

```sql
CREATE TABLE mdl_learningstylesurvey_userstyles (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único
    userid bigint(10) NOT NULL,               -- ID del usuario (FK a mdl_user)
    style varchar(50) NOT NULL,               -- Estilo detectado
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de detección
    PRIMARY KEY (id),
    KEY userid_fk (userid),
    CONSTRAINT userid_fk FOREIGN KEY (userid) REFERENCES mdl_user (id)
);
```

**Propósito**: Almacena el estilo de aprendizaje detectado para cada usuario.

**Valores de `style`**:
- `'Activo'`: Aprende haciendo, experimenta
- `'Reflexivo'`: Aprende pensando, observa
- `'Sensorial'`: Orientado a hechos y datos
- `'Intuitivo'`: Orientado a teorías e ideas
- `'Visual'`: Aprende mejor con imágenes
- `'Verbal'`: Aprende mejor con texto/audio
- `'Secuencial'`: Aprende paso a paso
- `'Global'`: Necesita ver el panorama completo

#### **Tabla: `learningstylesurvey_responses` (Respuestas de Encuesta)**

```sql
CREATE TABLE mdl_learningstylesurvey_responses (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único
    surveyid bigint(10) NOT NULL,             -- ID de la instancia del módulo
    userid bigint(10) NOT NULL,               -- ID del usuario
    questionid bigint(10) NOT NULL,           -- Número de pregunta (1-44)
    response text NOT NULL,                   -- Respuesta (0 o 1)
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de respuesta
    PRIMARY KEY (id)
);
```

**Propósito**: Guarda cada respuesta individual del cuestionario ILS de 44 preguntas.

**Campos importantes**:
- `questionid`: Número de pregunta del 1 al 44
- `response`: "0" para primera opción, "1" para segunda opción

#### **Tabla: `learningstylesurvey_paths` (Rutas de Aprendizaje)**

```sql
CREATE TABLE mdl_learningstylesurvey_paths (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único de la ruta
    courseid bigint(10) NOT NULL DEFAULT 0,   -- ID del curso
    userid bigint(10) NOT NULL DEFAULT 0,     -- ID del profesor creador
    name varchar(255) NOT NULL DEFAULT '',    -- Nombre de la ruta
    filename varchar(255) NOT NULL DEFAULT '', -- Archivo asociado (si aplica)
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de creación
    cmid bigint(10) DEFAULT NULL,             -- Course Module ID
    PRIMARY KEY (id)
);
```

**Propósito**: Define rutas de aprendizaje personalizadas por curso.

**Uso**: Una ruta agrupa múltiples pasos (recursos y evaluaciones) en secuencia.

#### **Tabla: `learningpath_steps` (Pasos de Ruta) ⭐ TABLA PRINCIPAL**

```sql
CREATE TABLE mdl_learningpath_steps (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único del paso
    pathid bigint(10) NOT NULL,               -- ID de la ruta padre
    stepnumber int(5) NOT NULL,               -- Número de orden del paso
    resourceid bigint(10) NOT NULL,           -- ID del recurso o quiz
    istest tinyint(1) NOT NULL DEFAULT 0,     -- 1=examen, 0=recurso
    passredirect bigint(10) DEFAULT NULL,     -- Tema destino si aprueba
    failredirect bigint(10) DEFAULT NULL,     -- Tema destino si reprueba
    PRIMARY KEY (id)
);
```

**Propósito**: ⭐ **TABLA PRINCIPAL** para navegación. Define la secuencia de pasos en una ruta.

**Campos críticos**:
- `stepnumber`: Orden del paso (1, 2, 3...)
- `resourceid`: Si `istest=0` apunta a `learningstylesurvey_resources.id`, si `istest=1` apunta a `learningstylesurvey_quizzes.id`
- `istest`: Determina si el paso es un recurso didáctico (0) o una evaluación (1)
- `passredirect`: ID del tema al que se redirige si aprueba el examen
- `failredirect`: ID del tema al que se redirige si reprueba (tema de refuerzo)

#### **Tabla: `learningstylesurvey_user_progress` (Progreso de Usuario)**

```sql
CREATE TABLE mdl_learningstylesurvey_user_progress (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único
    userid bigint(10) NOT NULL,               -- ID del usuario
    pathid bigint(10) NOT NULL,               -- ID de la ruta
    current_stepid bigint(10) NOT NULL,       -- ID del paso actual
    status varchar(20) NOT NULL DEFAULT 'inprogress', -- Estado del progreso
    timemodified bigint(10) NOT NULL DEFAULT 0, -- Última modificación
    PRIMARY KEY (id),
    KEY userfk (userid),
    KEY pathfk (pathid),
    KEY stepfk (current_stepid),
    CONSTRAINT userfk FOREIGN KEY (userid) REFERENCES mdl_user (id),
    CONSTRAINT pathfk FOREIGN KEY (pathid) REFERENCES mdl_learningstylesurvey_paths (id),
    CONSTRAINT stepfk FOREIGN KEY (current_stepid) REFERENCES mdl_learningpath_steps (id)
);
```

**Propósito**: Rastrea el progreso de cada estudiante en su ruta asignada.

**Valores de `status`**:
- `'inprogress'`: Cursando la ruta
- `'completed'`: Ruta completada
- `'blocked'`: Bloqueado por reproba

#### **Tabla: `learningstylesurvey_resources` (Recursos Didácticos)**

```sql
CREATE TABLE mdl_learningstylesurvey_resources (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único del recurso
    courseid bigint(10) NOT NULL,             -- ID del curso
    name varchar(255) NOT NULL,               -- Nombre del recurso
    filename varchar(255) NOT NULL,           -- Nombre del archivo
    style varchar(50) NOT NULL,               -- Estilo de aprendizaje
    tema bigint(10) DEFAULT NULL,             -- ID del tema (NO temaid)
    userid bigint(10) NOT NULL DEFAULT 0,     -- ID del profesor creador
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de creación
    recoveryquizid bigint(10) DEFAULT NULL,   -- Quiz de recuperación asociado
    PRIMARY KEY (id)
);
```

**Propósito**: Almacena archivos didácticos (PDFs, videos, imágenes) asociados a estilos de aprendizaje.

**⚠️ IMPORTANTE**:
- Campo es `tema` (NO `temaid`)
- `userid` es del **profesor que creó** el recurso, NO del estudiante
- `style` debe coincidir exactamente con los valores en `learningstylesurvey_userstyles.style`

#### **Tabla: `learningstylesurvey_temas` (Temas Educativos)**

```sql
CREATE TABLE mdl_learningstylesurvey_temas (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único del tema
    courseid bigint(10) NOT NULL,             -- ID del curso
    tema text NOT NULL,                       -- Descripción del tema
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de creación
    userid bigint(10) NOT NULL DEFAULT 0,     -- ID del profesor creador
    PRIMARY KEY (id)
);
```

**Propósito**: Define temas educativos por curso para organizar recursos y evaluaciones.

**Uso**: Los temas agrupan recursos y se usan en la navegación adaptativa.

#### **Tabla: `learningstylesurvey_quizzes` (Exámenes/Evaluaciones)**

```sql
CREATE TABLE mdl_learningstylesurvey_quizzes (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único del quiz
    name varchar(255) NOT NULL,               -- Nombre del examen
    userid bigint(10) NOT NULL,               -- ID del profesor creador
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de creación
    courseid bigint(10) DEFAULT 0,            -- ID del curso
    orden int(11) DEFAULT 0,                  -- Orden en la lista
    PRIMARY KEY (id)
);
```

**Propósito**: Define exámenes/evaluaciones creados por profesores.

**⚠️ NOTA**: Tabla se llama `quizzes` (NO `quiz`)

#### **Tabla: `learningstylesurvey_questions` (Preguntas de Examen)**

```sql
CREATE TABLE mdl_learningstylesurvey_questions (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único de la pregunta
    quizid bigint(10) NOT NULL,               -- ID del quiz padre
    questiontext text NOT NULL,               -- Texto de la pregunta
    correctanswer text NOT NULL,              -- Respuesta correcta
    PRIMARY KEY (id)
);
```

**Propósito**: Almacena preguntas de cada examen.

**Campos importantes**:
- `correctanswer`: Puede ser índice numérico (0,1,2,3) o texto de la respuesta correcta

#### **Tabla: `learningstylesurvey_options` (Opciones de Respuesta)**

```sql
CREATE TABLE mdl_learningstylesurvey_options (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único de la opción
    questionid bigint(10) NOT NULL,           -- ID de la pregunta padre
    optiontext text NOT NULL,                 -- Texto de la opción
    PRIMARY KEY (id)
);
```

**Propósito**: Define las opciones de respuesta para cada pregunta.

**⚠️ CRÍTICO**: Siempre ordenar por `id ASC` para mantener consistencia de índices.

#### **Tabla: `learningstylesurvey_quiz_results` (Resultados de Exámenes)**

```sql
CREATE TABLE mdl_learningstylesurvey_quiz_results (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único del resultado
    userid bigint(10) NOT NULL,               -- ID del estudiante
    quizid bigint(10) NOT NULL,               -- ID del quiz
    score int(10) NOT NULL,                   -- Puntuación (0-100)
    courseid bigint(10) NOT NULL,             -- ID del curso
    timecompleted bigint(10) NOT NULL,        -- Fecha de finalización
    timemodified bigint(10) DEFAULT 0,        -- Fecha de modificación
    PRIMARY KEY (id)
);
```

**Propósito**: Registra cada intento de examen por estudiante.

**⚠️ IMPORTANTE**: Cada intento debe ser un **nuevo registro** (INSERT), no UPDATE.

### 17.2. Tablas de Relación y Auxiliares

#### **Tabla: `learningstylesurvey_path_temas` (Relación Rutas-Temas)**

```sql
CREATE TABLE mdl_learningstylesurvey_path_temas (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único
    pathid bigint(10) NOT NULL,               -- ID de la ruta
    temaid bigint(10) NOT NULL,               -- ID del tema
    orden int(10) NOT NULL DEFAULT 0,         -- Orden en la ruta
    isrefuerzo tinyint(1) NOT NULL DEFAULT 0, -- 1=tema de refuerzo
    timecreated bigint(10) NOT NULL DEFAULT 0, -- Fecha de creación
    PRIMARY KEY (id),
    KEY path_fk (pathid),
    KEY tema_fk (temaid),
    CONSTRAINT path_fk FOREIGN KEY (pathid) REFERENCES mdl_learningstylesurvey_paths (id),
    CONSTRAINT tema_fk FOREIGN KEY (temaid) REFERENCES mdl_learningstylesurvey_temas (id)
);
```

**Propósito**: Relaciona rutas con temas y define cuáles son de refuerzo.

#### **Tabla: `learningstylesurvey_path_files` (Archivos por Ruta)**

```sql
CREATE TABLE mdl_learningstylesurvey_path_files (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único
    pathid bigint(10) NOT NULL,               -- ID de la ruta
    filename varchar(255) NOT NULL,           -- Nombre del archivo
    steporder int(10) DEFAULT 0,              -- Orden en la secuencia
    PRIMARY KEY (id),
    KEY path_fk (pathid),
    CONSTRAINT path_fk FOREIGN KEY (pathid) REFERENCES mdl_learningstylesurvey_paths (id)
);
```

#### **Tabla: `learningstylesurvey_path_evaluations` (Evaluaciones por Ruta)**

```sql
CREATE TABLE mdl_learningstylesurvey_path_evaluations (
    id bigint(10) NOT NULL AUTO_INCREMENT,     -- ID único
    pathid bigint(10) NOT NULL,               -- ID de la ruta
    quizid bigint(10) NOT NULL,               -- ID del quiz
    PRIMARY KEY (id),
    KEY path_fk (pathid),
    CONSTRAINT path_fk FOREIGN KEY (pathid) REFERENCES mdl_learningstylesurvey_paths (id)
);
```

### 17.3. Relaciones entre Tablas

```
learningstylesurvey (instancia)
    ↓
learningstylesurvey_paths (ruta por curso)
    ↓
learningpath_steps (pasos secuenciales) ← **TABLA PRINCIPAL**
    ↓
    ├── learningstylesurvey_resources (si istest=0)
    └── learningstylesurvey_quizzes (si istest=1)
            ↓
            ├── learningstylesurvey_questions
            │       ↓
            │   learningstylesurvey_options
            └── learningstylesurvey_quiz_results

learningstylesurvey_userstyles (estilo por usuario)
    ↓
learningstylesurvey_user_progress (progreso en ruta)
```

### 17.4. Consultas SQL Típicas

#### **Obtener estilo de un usuario**:
```sql
SELECT style 
FROM mdl_learningstylesurvey_userstyles 
WHERE userid = ? 
ORDER BY timecreated DESC 
LIMIT 1;
```

#### **Obtener recursos filtrados por estilo**:
```sql
SELECT * 
FROM mdl_learningstylesurvey_resources 
WHERE courseid = ? 
  AND style = LOWER(?) 
  AND tema = ?;
```

#### **Obtener pasos de una ruta**:
```sql
SELECT * 
FROM mdl_learningpath_steps 
WHERE pathid = ? 
ORDER BY stepnumber ASC;
```

#### **Obtener progreso actual del usuario**:
```sql
SELECT * 
FROM mdl_learningstylesurvey_user_progress 
WHERE userid = ? 
  AND pathid = ?;
```

#### **Verificar último resultado de quiz en ruta específica**:
```sql
SELECT qr.*, s.failredirect, s.passredirect
FROM mdl_learningstylesurvey_quiz_results qr
JOIN mdl_learningpath_steps s ON s.resourceid = qr.quizid AND s.istest = 1
WHERE qr.userid = ? 
  AND qr.courseid = ? 
  AND s.pathid = ?
ORDER BY qr.timecompleted DESC 
LIMIT 1;
```

---

## 18. Documentación Detallada de Archivos PHP

### 18.1. Archivos Principales del Módulo

#### **📄 `view.php` - Menú Principal del Módulo**

```php
// Ubicación: /mod/learningstylesurvey/view.php
// Propósito: Punto de entrada principal, muestra diferentes opciones según el rol
```

**Variables principales**:
- `$id`: Course Module ID obtenido por `required_param()`
- `$cm`: Objeto Course Module completo
- `$course`: Objeto curso asociado
- `$context`: Contexto para verificar permisos

**Funcionalidad**:
1. **Para estudiantes** (sin permiso `moodle/course:update`):
   - Botón principal: "Comenzar Ruta Aprendizaje Adaptativa"
   - Enlace a encuesta de estilos
   - Enlace a ver resultados

2. **Para profesores** (con permisos de edición):
   - Todas las opciones de estudiante +
   - Subir archivos
   - Gestionar temas
   - Crear evaluaciones
   - Gestionar exámenes
   - Configurar rutas de aprendizaje

3. **Para administradores**:
   - Todas las opciones anteriores +
   - Verificar funcionalidades (herramientas de diagnóstico)

**Navegación generada**:
```php
// Enlaces típicos generados
$vista_url = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', 
    ['courseid' => $course->id, 'cmid' => $id]);
    
$upload_url = new moodle_url('/mod/learningstylesurvey/resource/uploadresource.php', 
    ['courseid' => $course->id, 'cmid' => $id]);
```

#### **📄 `surveyform.php` - Encuesta de Estilos de Aprendizaje**

```php
// Propósito: Implementa el cuestionario ILS de 44 preguntas
// Algoritmo: Felder-Silverman Learning Styles Index
```

**Variables principales**:
- `$responses[]`: Array con respuestas del usuario (1-44)
- `$stylecounts[]`: Contadores por cada estilo de aprendizaje
- `$strongest_style`: Estilo dominante detectado

**Proceso de evaluación**:
1. **Recepción de datos**: Procesa POST con respuestas `ilsq1` a `ilsq44`
2. **Borrado anterior**: Elimina respuestas previas del usuario
3. **Guardado individual**: Cada respuesta se guarda en `learningstylesurvey_responses`
4. **Cálculo de estilos**: Aplica algoritmo de conteo por dimensiones
5. **Detección dominante**: Identifica el estilo más fuerte
6. **Persistencia**: Guarda resultado en `learningstylesurvey_userstyles`

**Algoritmo de cálculo**:
```php
// Estructura del cálculo
$stylecounts = [
    'Activo' => 0, 'Reflexivo' => 0,      // Preguntas 1,5,9,13,17,21,25,29,33,37,41
    'Sensorial' => 0, 'Intuitivo' => 0,   // Preguntas 2,6,10,14,18,22,26,30,34,38,42
    'Visual' => 0, 'Verbal' => 0,         // Preguntas 3,7,11,15,19,23,27,31,35,39,43
    'Secuencial' => 0, 'Global' => 0      // Preguntas 4,8,12,16,20,24,28,32,36,40,44
];

// Mapeo pregunta → [respuesta_0_incrementa, respuesta_1_incrementa]
$mapping = [
    1 => ['Activo', 'Reflexivo'],    // Si respuesta=0 → +1 Activo, si respuesta=1 → +1 Reflexivo
    2 => ['Sensorial', 'Intuitivo'], // Si respuesta=0 → +1 Sensorial, etc.
    // ... continúa para las 44 preguntas
];
```

#### **📄 `results.php` - Visualización de Resultados**

```php
// Propósito: Muestra resultados de encuestas y análisis de estilos
```

**Funcionalidad**:
- Recupera respuestas desde `learningstylesurvey_responses`
- Recalcula conteos por estilo
- Muestra gráficos de distribución
- Identifica estilo dominante
- Proporciona interpretación educativa

#### **📄 `mod_form.php` - Formulario de Configuración**

```php
// Propósito: Define el formulario que ve el profesor al agregar/editar el módulo
```

**Campos configurables**:
- `name`: Nombre de la instancia
- `intro`: Descripción introductoria
- `introformat`: Formato del texto
- Elementos estándar de Moodle (disponibilidad, restricciones, etc.)

### 18.2. Sistema de Rutas de Aprendizaje (`/path/`)

#### **📄 `path/vista_estudiante.php` - Interfaz Principal del Estudiante**

```php
// Propósito: Vista principal donde el estudiante ve su ruta personalizada
```

**Variables principales**:
```php
$courseid = required_param('courseid', PARAM_INT);    // ID del curso
$cmid = optional_param('cmid', 0, PARAM_INT);        // Course Module ID
$pathid = null;                                       // ID de la ruta asignada
$userstyle = null;                                    // Estilo del usuario
$current_step = null;                                 // Paso actual
$progress = null;                                     // Progreso del usuario
```

**Flujo de funcionamiento**:

1. **Detección de estilo**:
```php
$userstyle = $DB->get_record('learningstylesurvey_userstyles', 
    ['userid' => $USER->id], '*', IGNORE_MULTIPLE);
```

2. **Obtención de ruta**:
```php
$path = $DB->get_record('learningstylesurvey_paths', 
    ['courseid' => $courseid], '*', IGNORE_MULTIPLE);
```

3. **Verificación de progreso**:
```php
$progress = $DB->get_record('learningstylesurvey_user_progress', 
    ['userid' => $USER->id, 'pathid' => $pathid]);
```

4. **Carga del paso actual**:
```php
$current_step = $DB->get_record('learningpath_steps', 
    ['id' => $progress->current_stepid]);
```

5. **Filtrado de recursos por estilo**:
```php
if ($current_step->istest == 0) {
    // Es un recurso didáctico
    $recursos = $DB->get_records('learningstylesurvey_resources', [
        'tema' => $current_step->resourceid,
        'style' => strtolower(trim($userstyle->style)),
        'courseid' => $courseid
    ]);
} else {
    // Es una evaluación
    $quiz = $DB->get_record('learningstylesurvey_quizzes', 
        ['id' => $current_step->resourceid]);
}
```

**Lógica de navegación adaptativa**:
```php
// Verificar si debe ir a tema de refuerzo
$lastquiz = $DB->get_record_sql("
    SELECT qr.*, s.failredirect 
    FROM {learningstylesurvey_quiz_results} qr
    JOIN {learningpath_steps} s ON s.resourceid = qr.quizid AND s.istest = 1
    WHERE qr.userid = ? AND qr.courseid = ? AND s.pathid = ?
    ORDER BY qr.timecompleted DESC 
    LIMIT 1
", [$USER->id, $courseid, $pathid]);

if ($lastquiz && $lastquiz->score < 70 && $lastquiz->failredirect) {
    // Estudiante reprobó, mostrar tema de refuerzo
    $tema_refuerzo = $DB->get_record('learningstylesurvey_temas', 
        ['id' => $lastquiz->failredirect]);
}
```

#### **📄 `path/learningpath.php` - Gestión de Rutas (Profesor)**

```php
// Propósito: Interfaz del profesor para gestionar rutas de aprendizaje
```

**Funcionalidades principales**:
1. **Listar rutas existentes**
2. **Crear nueva ruta**
3. **Editar ruta existente**
4. **Eliminar ruta**
5. **Ver progreso de estudiantes**

**Variables clave**:
```php
$courseid = required_param('courseid', PARAM_INT);    // ID del curso
$action = optional_param('action', 'list', PARAM_ALPHA); // Acción: list, create, edit, delete
$pathid = optional_param('pathid', 0, PARAM_INT);    // ID de ruta para editar/eliminar
```

#### **📄 `path/createsteproute.php` - Creador de Rutas**

```php
// Propósito: Formulario para crear nuevas rutas de aprendizaje paso a paso
```

**Variables principales**:
```php
$courseid = required_param('courseid', PARAM_INT);
$nombre = '';                          // Nombre de la ruta
$temas_ids = [];                      // Array de IDs de temas seleccionados
$archivos = [];                       // Array de recursos disponibles
$evaluaciones_array = [];             // Array de evaluaciones seleccionadas
$refuerzo_ids = [];                   // IDs de temas marcados como refuerzo
$saltos_pass = [];                    // Configuración de saltos al aprobar
$saltos_fail = [];                    // Configuración de saltos al reprobar
```

**Proceso de creación**:

1. **Selección de temas**:
```javascript
// JavaScript para gestión dinámica
function addTema(select) {
    const temaId = select.value;
    const temaText = select.options[select.selectedIndex].text;
    // Añadir tema a la lista de seleccionados
    // Actualizar campos ocultos para envío
}
```

2. **Configuración de saltos**:
```php
// Procesar campos de redirección
$saltos_aprueba = optional_param('saltos_aprueba', '', PARAM_RAW);
$saltos_reprueba = optional_param('saltos_reprueba', '', PARAM_RAW);

// Formato: "tema_id:type:target_id|tema_id:type:target_id"
foreach (explode('|', $saltos_aprueba) as $salto) {
    if (!empty($salto)) {
        $parts = explode(':', $salto);
        if (count($parts) === 3) {
            $saltos_pass[$parts[0]] = ['type' => $parts[1], 'id' => $parts[2]];
        }
    }
}
```

3. **Guardado en base de datos**:
```php
// Crear registro principal en learningstylesurvey_paths
$ruta = new stdClass();
$ruta->courseid = $courseid;
$ruta->userid = $USER->id;
$ruta->cmid = $cmid;
$ruta->name = $nombre;
$ruta->timecreated = time();
$pathid = $DB->insert_record('learningstylesurvey_paths', $ruta);

// Guardar pasos en learningpath_steps
foreach ($temas_ids as $orden => $tema_id) {
    $record = new stdClass();
    $record->pathid = $pathid;
    $record->temaid = $tema_id;
    $record->orden = $orden + 1;
    $record->isrefuerzo = in_array($tema_id, $refuerzo_ids) ? 1 : 0;
    $DB->insert_record('learningstylesurvey_path_temas', $record);
}
```

#### **📄 `path/edit_learningpath.php` - Editor de Rutas**

```php
// Propósito: Permite modificar rutas existentes y reordenar pasos
```

**Funcionalidades**:
- Editar nombre de la ruta
- Reordenar pasos mediante drag & drop
- Modificar configuración de saltos
- Añadir/eliminar pasos
- Resetear progreso de estudiantes si se modifica la estructura

#### **📄 `path/siguiente.php` - Lógica de Avance**

```php
// Propósito: Controla el avance del estudiante al siguiente paso
```

**Variables principales**:
```php
$pathid = required_param('pathid', PARAM_INT);        // ID de la ruta
$current_stepid = required_param('stepid', PARAM_INT); // ID del paso actual
$action = optional_param('action', 'next', PARAM_ALPHA); // Acción: next, complete
```

**Lógica de avance**:
```php
// Obtener paso actual
$current_step = $DB->get_record('learningpath_steps', ['id' => $current_stepid]);

// Obtener siguiente paso en secuencia
$next_step = $DB->get_record('learningpath_steps', [
    'pathid' => $pathid,
    'stepnumber' => $current_step->stepnumber + 1
]);

if ($next_step) {
    // Actualizar progreso al siguiente paso
    $progress->current_stepid = $next_step->id;
    $DB->update_record('learningstylesurvey_user_progress', $progress);
} else {
    // Marcar ruta como completada
    $progress->status = 'completed';
    $DB->update_record('learningstylesurvey_user_progress', $progress);
}
```

#### **📄 `path/guardar_orden.php` - Guardado de Orden AJAX**

```php
// Propósito: Endpoint AJAX para guardar orden de pasos modificado por drag & drop
```

**Entrada esperada** (JSON):
```json
[
    {
        "id": 123,
        "orden": 1,
        "tipo": "tema",
        "passredirect": 456,
        "failredirect": 789,
        "isrefuerzo": 0
    },
    // ... más elementos
]
```

**Proceso**:
1. **Validación de datos**:
```php
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}
```

2. **Actualización por tipo**:
```php
foreach ($data as $item) {
    $id = intval($item['id']);
    $orden = intval($item['orden']);
    $tipo = $item['tipo'] ?? 'tema';
    
    if ($tipo === 'examen') {
        // Actualizar paso de examen
        $step = $DB->get_record('learningpath_steps', ['resourceid' => $id, 'istest' => 1]);
        if ($step) {
            $step->stepnumber = $orden;
            $step->passredirect = $passredirect;
            $step->failredirect = $failredirect;
            $DB->update_record('learningpath_steps', $step);
        }
    }
}
```

### 18.3. Sistema de Recursos (`/resource/`)

#### **📄 `resource/uploadresource.php` - Subida de Archivos**

```php
// Propósito: Permite a profesores subir recursos didácticos por estilo de aprendizaje
```

**Variables principales**:
```php
$courseid = required_param('courseid', PARAM_INT);    // ID del curso
$style = required_param('style', PARAM_ALPHA);        // Estilo seleccionado
$tema_id = required_param('tema', PARAM_INT);         // ID del tema
$file = $_FILES['resource_file'];                     // Archivo subido
```

**Proceso de subida**:

1. **Validación del archivo**:
```php
$allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'doc', 'docx', 'ppt', 'pptx'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_types)) {
    throw new moodle_exception('Tipo de archivo no permitido');
}
```

2. **Creación de directorio**:
```php
$upload_dir = learningstylesurvey_ensure_upload_directory($courseid);
$final_path = $upload_dir . $final_filename;
```

3. **Guardado físico y en BD**:
```php
if (move_uploaded_file($file['tmp_name'], $final_path)) {
    $record = new stdClass();
    $record->courseid = $courseid;
    $record->name = $file_name;
    $record->filename = $final_filename;
    $record->style = strtolower($style);
    $record->tema = $tema_id;
    $record->userid = $USER->id;
    $record->timecreated = time();
    
    $DB->insert_record('learningstylesurvey_resources', $record);
}
```

#### **📄 `resource/viewresources.php` - Visualizador de Recursos**

```php
// Propósito: Lista todos los recursos subidos, con filtros por estilo y tema
```

**Filtros disponibles**:
- Por estilo de aprendizaje
- Por tema educativo
- Por fecha de creación
- Por profesor creador

**Consulta típica**:
```php
$sql = "SELECT r.*, t.tema as tema_nombre, u.firstname, u.lastname 
        FROM {learningstylesurvey_resources} r
        LEFT JOIN {learningstylesurvey_temas} t ON r.tema = t.id
        LEFT JOIN {user} u ON r.userid = u.id
        WHERE r.courseid = ?";

$params = [$courseid];

if ($style_filter) {
    $sql .= " AND r.style = ?";
    $params[] = $style_filter;
}

if ($tema_filter) {
    $sql .= " AND r.tema = ?";
    $params[] = $tema_filter;
}

$resources = $DB->get_records_sql($sql, $params);
```

#### **📄 `resource/ver_recurso.php` - Visualización Individual**

```php
// Propósito: Muestra un recurso específico al estudiante
```

**Variables principales**:
```php
$resource_id = required_param('id', PARAM_INT);       // ID del recurso
$courseid = required_param('courseid', PARAM_INT);    // ID del curso
$pathid = optional_param('pathid', 0, PARAM_INT);     // ID de la ruta (para navegación)
$stepid = optional_param('stepid', 0, PARAM_INT);     // ID del paso actual
```

**Funcionalidad**:
- Verifica permisos de acceso
- Determina tipo de archivo (imagen, PDF, video)
- Renderiza visualizador apropiado
- Proporciona navegación de vuelta a la ruta

#### **📄 `resource/temas.php` - Gestión de Temas**

```php
// Propósito: CRUD de temas educativos por curso
```

**Operaciones soportadas**:
1. **Crear tema**:
```php
if ($_POST['action'] === 'create') {
    $tema = new stdClass();
    $tema->courseid = $courseid;
    $tema->tema = trim($_POST['tema']);
    $tema->userid = $USER->id;
    $tema->timecreated = time();
    
    $DB->insert_record('learningstylesurvey_temas', $tema);
}
```

2. **Editar tema**:
```php
if ($_POST['action'] === 'edit') {
    $tema = $DB->get_record('learningstylesurvey_temas', ['id' => $tema_id]);
    $tema->tema = trim($_POST['tema']);
    
    $DB->update_record('learningstylesurvey_temas', $tema);
}
```

3. **Eliminar tema**:
```php
if ($_POST['action'] === 'delete') {
    // Verificar que no tenga recursos asociados
    $count = $DB->count_records('learningstylesurvey_resources', ['tema' => $tema_id]);
    
    if ($count > 0) {
        throw new moodle_exception('No se puede eliminar tema con recursos asociados');
    }
    
    $DB->delete_records('learningstylesurvey_temas', ['id' => $tema_id]);
}
```

### 18.4. Sistema de Evaluaciones (`/quiz/`)

#### **📄 `quiz/crear_examen.php` - Creador de Exámenes**

```php
// Propósito: Interfaz para crear exámenes con preguntas y opciones múltiples
```

**Variables principales**:
```php
$courseid = required_param('courseid', PARAM_INT);    // ID del curso
$quiz_name = '';                                      // Nombre del examen
$questions = [];                                      // Array de preguntas
$num_questions = 1;                                   // Número de preguntas a crear
```

**Estructura del formulario**:
```html
<form method="post">
    <input type="text" name="quiz_name" placeholder="Nombre del examen" required>
    <input type="number" name="num_questions" value="1" min="1" max="20">
    
    <!-- Preguntas generadas dinámicamente -->
    <div id="questions-container">
        <!-- Se generan por JavaScript según num_questions -->
    </div>
    
    <button type="submit">Crear Examen</button>
</form>
```

**Proceso de guardado**:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Crear registro del quiz
    $quiz = new stdClass();
    $quiz->name = trim($_POST['quiz_name']);
    $quiz->userid = $USER->id;
    $quiz->courseid = $courseid;
    $quiz->timecreated = time();
    $quiz->orden = 0;
    
    $quizid = $DB->insert_record('learningstylesurvey_quizzes', $quiz);
    
    // 2. Procesar cada pregunta
    for ($i = 1; $i <= $num_questions; $i++) {
        $question_text = trim($_POST["question_$i"]);
        $correct_answer = intval($_POST["correct_$i"]);
        
        // Crear pregunta
        $question = new stdClass();
        $question->quizid = $quizid;
        $question->questiontext = $question_text;
        $question->correctanswer = $correct_answer; // Índice 0-3
        
        $questionid = $DB->insert_record('learningstylesurvey_questions', $question);
        
        // 3. Crear opciones de respuesta
        for ($j = 0; $j < 4; $j++) {
            $option_text = trim($_POST["option_{$i}_{$j}"]);
            
            if (!empty($option_text)) {
                $option = new stdClass();
                $option->questionid = $questionid;
                $option->optiontext = $option_text;
                
                $DB->insert_record('learningstylesurvey_options', $option);
            }
        }
    }
}
```

#### **📄 `quiz/manage_quiz.php` - Gestión de Exámenes**

```php
// Propósito: Lista, edita y elimina exámenes existentes
```

**Funcionalidades**:
1. **Listar exámenes del curso**
2. **Editar preguntas y opciones**
3. **Eliminar exámenes**
4. **Ver estadísticas de resultados**

**Consulta para listar**:
```php
$quizzes = $DB->get_records_sql("
    SELECT q.*, 
           COUNT(DISTINCT qst.id) as num_questions,
           COUNT(DISTINCT qr.id) as num_attempts,
           AVG(qr.score) as avg_score
    FROM {learningstylesurvey_quizzes} q
    LEFT JOIN {learningstylesurvey_questions} qst ON q.id = qst.quizid
    LEFT JOIN {learningstylesurvey_quiz_results} qr ON q.id = qr.quizid
    WHERE q.courseid = ?
    GROUP BY q.id
    ORDER BY q.timecreated DESC
", [$courseid]);
```

#### **📄 `quiz/responder_quiz.php` - Interfaz de Examen**

```php
// Propósito: Permite a estudiantes responder exámenes y procesa las calificaciones
```

**Variables principales**:
```php
$quizid = required_param('quizid', PARAM_INT);        // ID del examen
$courseid = required_param('courseid', PARAM_INT);    // ID del curso
$pathid = optional_param('pathid', 0, PARAM_INT);     // ID de la ruta (navegación)
$stepid = optional_param('stepid', 0, PARAM_INT);     // ID del paso actual
```

**Proceso de evaluación**:

1. **Carga de preguntas**:
```php
$questions = $DB->get_records('learningstylesurvey_questions', ['quizid' => $quizid]);
foreach ($questions as $q) {
    $q->options = $DB->get_records('learningstylesurvey_options', 
        ['questionid' => $q->id], 'id ASC'); // ⚠️ CRÍTICO: ordenar por id ASC
}
```

2. **Procesamiento de respuestas**:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_questions = count($questions);
    $correct_answers = 0;
    
    foreach ($questions as $q) {
        $user_answer = isset($_POST["question_$q->id"]) ? 
            intval($_POST["question_$q->id"]) : null;
        
        if ($user_answer !== null) {
            // Obtener opciones ordenadas
            $options = $DB->get_records('learningstylesurvey_options', 
                ['questionid' => $q->id], 'id ASC');
            $options_array = array_values($options);
            
            // Encontrar índice de la respuesta seleccionada
            $selected_index = null;
            $option_index = 0;
            foreach ($options as $opt) {
                if ($opt->id == $user_answer) {
                    $selected_index = $option_index;
                    break;
                }
                $option_index++;
            }
            
            // Comparar con respuesta correcta
            if ($selected_index !== null && $selected_index == intval($q->correctanswer)) {
                $correct_answers++;
            }
        }
    }
    
    $score = $total_questions > 0 ? 
        round(($correct_answers / $total_questions) * 100) : 0;
    
    // 3. Guardar resultado
    $result = new stdClass();
    $result->userid = $USER->id;
    $result->quizid = $quizid;
    $result->score = $score;
    $result->courseid = $courseid;
    $result->timecompleted = time();
    $result->timemodified = time();
    
    $DB->insert_record('learningstylesurvey_quiz_results', $result); // ⚠️ INSERT, no UPDATE
}
```

3. **Lógica de navegación adaptativa**:
```php
if ($pathid && $stepid) {
    $current_step = $DB->get_record('learningpath_steps', ['id' => $stepid]);
    
    if ($score >= 70 && $current_step->passredirect) {
        // Aprobó: avanzar al siguiente tema
        $redirect_url = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $pathid,
            'tema_id' => $current_step->passredirect
        ]);
    } elseif ($score < 70 && $current_step->failredirect) {
        // Reprobó: ir a tema de refuerzo
        $redirect_url = new moodle_url('/mod/learningstylesurvey/path/vista_estudiante.php', [
            'courseid' => $courseid,
            'pathid' => $pathid,
            'tema_id' => $current_step->failredirect
        ]);
    }
    
    redirect($redirect_url);
}
```

#### **📄 `quiz/guardar_examen.php` - Procesador de Guardado**

```php
// Propósito: Endpoint para guardar/actualizar exámenes via AJAX
```

**Entrada esperada**:
```json
{
    "quiz_name": "Nombre del examen",
    "questions": [
        {
            "text": "¿Pregunta 1?",
            "correct": 2,
            "options": ["Opción A", "Opción B", "Opción C", "Opción D"]
        }
    ]
}
```

**Respuesta**:
```json
{
    "success": true,
    "quiz_id": 123,
    "message": "Examen guardado correctamente"
}
```

### 18.5. Archivos de Utilidades (`/utils/`)

#### **📄 `utils/verificar_funcionalidades.php` - Herramienta de Diagnóstico**

```php
// Propósito: Herramienta de diagnóstico completo del sistema (solo administradores)
```

**Verificaciones realizadas**:

1. **Estructura de base de datos**:
```php
$tables_to_check = [
    'learningstylesurvey' => 'Tabla principal del módulo',
    'learningstylesurvey_temas' => 'Gestión de temas por curso',
    'learningstylesurvey_resources' => 'Archivos subidos por estilo',
    'learningstylesurvey_quizzes' => 'Cuestionarios de evaluación',
    'learningstylesurvey_questions' => 'Preguntas de los quizzes',
    'learningstylesurvey_options' => 'Opciones de respuesta',
    'learningstylesurvey_quiz_results' => 'Resultados de evaluaciones',
    'learningstylesurvey_paths' => 'Rutas de aprendizaje personalizadas'
];

foreach ($tables_to_check as $table => $description) {
    if ($DB->get_manager()->table_exists($table)) {
        echo "✅ {$table} - {$description}";
    } else {
        echo "❌ {$table} - NO EXISTE";
    }
}
```

2. **Verificación de campos críticos**:
```php
$critical_fields = [
    'learningstylesurvey_quiz_results' => ['timemodified', 'userid', 'quizid', 'score'],
    'learningstylesurvey_resources' => ['tema', 'style', 'userid', 'courseid'],
    'learningstylesurvey_questions' => ['correctanswer', 'questiontext'],
    'learningpath_steps' => ['passredirect', 'failredirect', 'istest'],
    'learningstylesurvey_userstyles' => ['style', 'timecreated']
];

foreach ($critical_fields as $table => $fields) {
    if ($DB->get_manager()->table_exists($table)) {
        foreach ($fields as $field) {
            $field_obj = new xmldb_field($field);
            if ($DB->get_manager()->field_exists(new xmldb_table($table), $field_obj)) {
                echo "✅ {$table}.{$field}";
            } else {
                echo "❌ {$table}.{$field} - FALTA";
            }
        }
    }
}
```

3. **Estadísticas del sistema**:
```php
// Conteo de datos en el curso actual
$total_usuarios_con_estilo = $DB->count_records_sql("
    SELECT COUNT(DISTINCT userid) 
    FROM {learningstylesurvey_userstyles}
");

$total_recursos = $DB->count_records('learningstylesurvey_resources', 
    ['courseid' => $courseid]);

$total_temas = $DB->count_records('learningstylesurvey_temas', 
    ['courseid' => $courseid]);

$total_quizzes = $DB->count_records('learningstylesurvey_quizzes', 
    ['courseid' => $courseid]);

$total_rutas = $DB->count_records('learningstylesurvey_paths', 
    ['courseid' => $courseid]);
```

4. **Verificación de funciones**:
```php
$functions = [
    'learningstylesurvey_ensure_upload_directory',
    'learningstylesurvey_migrate_files'
];

foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "✅ {$function} - Disponible";
        
        // Probar función con datos de prueba
        try {
            if ($function === 'learningstylesurvey_ensure_upload_directory') {
                $result = $function($courseid);
                echo "📁 Directorio: {$result}";
            }
        } catch (Exception $e) {
            echo "⚠️ Error al probar {$function}: " . $e->getMessage();
        }
    } else {
        echo "❌ {$function} - NO Disponible";
    }
}
```

5. **Verificación de accesibilidad web**:
```php
$urls_to_check = [
    'Vista principal' => '/mod/learningstylesurvey/view.php?id=' . $cmid,
    'Encuesta' => '/mod/learningstylesurvey/surveyform.php?id=' . $cmid,
    'Resultados' => '/mod/learningstylesurvey/results.php?id=' . $cmid,
    'Subir recursos' => '/mod/learningstylesurvey/resource/uploadresource.php?courseid=' . $courseid,
    'Vista estudiante' => '/mod/learningstylesurvey/path/vista_estudiante.php?courseid=' . $courseid
];

foreach ($urls_to_check as $name => $url) {
    $full_url = $CFG->wwwroot . $url;
    
    // Verificar que el archivo existe físicamente
    $file_path = $CFG->dirroot . $url;
    if (file_exists($file_path)) {
        echo "✅ {$name} - Archivo existe";
        echo "🔗 <a href='{$full_url}' target='_blank'>Probar enlace</a>";
    } else {
        echo "❌ {$name} - Archivo NO existe";
    }
}
```

#### **📄 `utils/verificar_completo.php` - Verificación de Rutas**

```php
// Propósito: Verifica que todos los archivos del proyecto existan y sean accesibles
```

**Archivos verificados**:
```php
$critical_files = [
    'Archivo principal' => 'view.php',
    'Configuración' => 'lib.php',
    'Formulario' => 'mod_form.php',
    'Resultados' => 'results.php',
    'Encuesta' => 'surveyform.php'
];

$subdirs = [
    'resource' => ['uploadresource.php', 'viewresources.php', 'ver_recurso.php', 'temas.php'],
    'quiz' => ['crear_examen.php', 'guardar_examen.php', 'manage_quiz.php', 'responder_quiz.php'],
    'path' => ['learningpath.php', 'vista_estudiante.php', 'createsteproute.php', 'siguiente.php']
];
```

### 18.6. Archivos de Configuración

#### **📄 `lib.php` - Funciones Principales de Moodle**

```php
// Propósito: Implementa las funciones estándar requeridas por Moodle para módulos
```

**Funciones obligatorias**:

1. **Crear instancia**:
```php
function learningstylesurvey_add_instance($data, $mform) {
    global $DB;
    
    $data->timecreated = time();
    return $DB->insert_record('learningstylesurvey', $data);
}
```

2. **Actualizar instancia**:
```php
function learningstylesurvey_update_instance($data, $mform) {
    global $DB;
    
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('learningstylesurvey', $data);
}
```

3. **Eliminar instancia**:
```php
function learningstylesurvey_delete_instance($id) {
    global $DB;
    
    if (!$record = $DB->get_record('learningstylesurvey', ['id' => $id])) {
        return false;
    }
    
    // Eliminar datos relacionados
    $DB->delete_records('learningstylesurvey_responses', ['surveyid' => $id]);
    $DB->delete_records('learningstylesurvey', ['id' => $id]);
    
    return true;
}
```

**Funciones auxiliares**:
```php
function learningstylesurvey_ensure_upload_directory($courseid) {
    global $CFG;
    
    $upload_dir = $CFG->dataroot . '/learningstylesurvey/' . $courseid . '/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new moodle_exception('cannotcreatedirectory', 'learningstylesurvey');
        }
    }
    
    return $upload_dir;
}
```

#### **📄 `version.php` - Información del Plugin**

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_learningstylesurvey';    // Nombre completo del plugin
$plugin->version = 2025091000;                     // Versión en formato YYYYMMDDXX
$plugin->requires = 2020110900;                    // Versión mínima de Moodle requerida
$plugin->maturity = MATURITY_STABLE;               // Nivel de madurez
$plugin->release = 'v1.0.0';                       // Versión legible para humanos
```

#### **📄 `db/access.php` - Definición de Permisos**

```php
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'mod/learningstylesurvey:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'guest' => CAP_PREVENT,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],
    
    'mod/learningstylesurvey:addinstance' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ]
];
```

---

## 19. Guía Completa de Instalación y Configuración

### 19.1. Requisitos del Sistema

#### **Requisitos Mínimos**:
- **Moodle**: Versión 3.9 o superior
- **PHP**: 7.2 o superior
- **Base de datos**: MySQL 5.7+ / PostgreSQL 10+ / MariaDB 10.2+
- **Espacio en disco**: 100 MB mínimo para archivos del plugin
- **Memoria PHP**: 128 MB mínimo (recomendado 256 MB)
- **Extensiones PHP**: fileinfo, gd, curl, zip

#### **Permisos requeridos**:
- **Directorio dataroot**: Lectura/escritura para crear subdirectorios
- **Directorio plugin**: Lectura/ejecución
- **Base de datos**: CREATE, ALTER, INSERT, UPDATE, DELETE, SELECT

### 19.2. Proceso de Instalación Paso a Paso

#### **Paso 1: Descarga e instalación de archivos**

```bash
# Opción A: Clonar desde repositorio
cd /path/to/moodle/mod/
git clone https://github.com/EderPG/learningstylesurvey.git learningstylesurvey

# Opción B: Descarga manual
wget https://github.com/EderPG/learningstylesurvey/archive/main.zip
unzip main.zip
mv learningstylesurvey-main /path/to/moodle/mod/learningstylesurvey

# Establecer permisos correctos
chown -R www-data:www-data /path/to/moodle/mod/learningstylesurvey
chmod -R 755 /path/to/moodle/mod/learningstylesurvey
```

#### **Paso 2: Instalación via interfaz web**

1. **Acceder como administrador** a tu sitio Moodle
2. **Ir a**: Administración del sitio > Notificaciones
3. **Seguir el asistente** de instalación de plugins
4. **Confirmar** la creación de tablas en base de datos

#### **Paso 3: Verificación de instalación**

```php
// Verificar en base de datos que las tablas se crearon
SHOW TABLES LIKE 'mdl_learningstylesurvey%';

// Resultado esperado:
// mdl_learningstylesurvey
// mdl_learningstylesurvey_userstyles
// mdl_learningstylesurvey_responses
// mdl_learningstylesurvey_paths
// mdl_learningpath_steps
// mdl_learningstylesurvey_resources
// mdl_learningstylesurvey_temas
// mdl_learningstylesurvey_quizzes
// mdl_learningstylesurvey_questions
// mdl_learningstylesurvey_options
// mdl_learningstylesurvey_quiz_results
// mdl_learningstylesurvey_user_progress
// ... (y otras tablas auxiliares)
```

#### **Paso 4: Configuración inicial**

1. **Crear directorio de subidas**:
```php
// Se crea automáticamente, pero verificar permisos
$upload_base = $CFG->dataroot . '/learningstylesurvey/';
mkdir($upload_base, 0755, true);
chown($upload_base, 'www-data');
```

2. **Verificar funcionalidades** (como administrador):
   - Ir a cualquier curso
   - Agregar actividad "Encuesta ILS"
   - Acceder al módulo
   - Usar la opción "🔧 Verificar Funcionalidades"

### 19.3. Configuración por Roles

#### **Para Administradores**:

```php
// Configuraciones recomendadas en config.php
$CFG->maxbytes = 50 * 1024 * 1024; // 50MB máximo por archivo
$CFG->debug = DEBUG_MINIMAL;        // Logging básico en producción

// Configurar límites de subida para recursos
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', 300);
```

#### **Permisos por defecto**:
- **Estudiantes**: Ver módulo, responder encuestas, acceder a su ruta
- **Profesores**: Todo lo anterior + crear recursos, exámenes, gestionar rutas
- **Administradores**: Acceso completo + herramientas de diagnóstico

### 19.4. Configuración Avanzada

#### **Personalización de estilos de aprendizaje**:

```php
// En locallib.php o archivo personalizado
function custom_learning_styles_calculation($responses) {
    // Implementar algoritmo personalizado si se requiere
    // Mantener compatibilidad con valores estándar:
    $valid_styles = ['activo', 'reflexivo', 'sensorial', 'intuitivo', 
                     'visual', 'verbal', 'secuencial', 'global'];
    
    // Retornar uno de los estilos válidos
    return $calculated_style;
}
```

#### **Configuración de tipos de archivo permitidos**:

```php
// En uploadresource.php - modificar según necesidades
$allowed_types = [
    // Documentos
    'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
    // Imágenes
    'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
    // Videos
    'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm',
    // Audio
    'mp3', 'wav', 'ogg', 'm4a',
    // Comprimidos
    'zip', 'rar', '7z',
    // Otros
    'txt', 'html', 'swf'
];
```

#### **Configuración de puntuaciones**:

```php
// Modificar umbral de aprobación por defecto
define('LEARNINGSTYLESURVEY_PASSING_SCORE', 70); // 70% por defecto

// En responder_quiz.php
$passing_score = get_config('learningstylesurvey', 'passing_score') ?: 70;

if ($score >= $passing_score) {
    // Aprobó
} else {
    // Reprobó - ir a refuerzo
}
```

### 19.5. Migración y Actualización

#### **Actualización desde versiones anteriores**:

```bash
# 1. Hacer backup de la base de datos
mysqldump -u usuario -p basededatos > backup_antes_actualizacion.sql

# 2. Hacer backup de archivos
cp -r /path/to/moodle/mod/learningstylesurvey /backup/learningstylesurvey_old

# 3. Reemplazar archivos
rm -rf /path/to/moodle/mod/learningstylesurvey
# Instalar nueva versión según Paso 1

# 4. Ejecutar upgrade via web
# Acceder como admin -> Administración del sitio -> Notificaciones
```

#### **Script de migración de datos** (si es necesario):

```php
// utils/migrate_data.php
<?php
require_once('../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

// Migrar tabla antigua learningstylesurvey_results a learningstylesurvey_userstyles
$old_results = $DB->get_records('learningstylesurvey_results');

foreach ($old_results as $old) {
    $exists = $DB->get_record('learningstylesurvey_userstyles', ['userid' => $old->userid]);
    
    if (!$exists) {
        $new_style = new stdClass();
        $new_style->userid = $old->userid;
        $new_style->style = $old->strongeststyle;
        $new_style->timecreated = time();
        
        $DB->insert_record('learningstylesurvey_userstyles', $new_style);
    }
}

echo "Migración completada";
?>
```

---

## 20. Casos de Uso y Ejemplos Prácticos

### 20.1. Caso de Uso 1: Configuración Inicial de un Curso

#### **Escenario**: Profesor configura por primera vez el sistema en su curso

**Pasos del profesor**:

1. **Agregar la actividad**:
   - Ir al curso → Activar edición → Agregar actividad → Encuesta ILS
   - Nombrar: "Sistema de Aprendizaje Adaptativo"
   - Descripción: "Complete la encuesta para recibir una ruta personalizada"

2. **Crear temas educativos**:
```php
// Via interface: /resource/temas.php
Temas creados:
- "Introducción al tema" (ID: 1)
- "Conceptos básicos" (ID: 2)  
- "Ejercicios prácticos" (ID: 3)
- "Evaluación intermedia" (ID: 4)
- "Temas avanzados" (ID: 5)
- "Refuerzo - Conceptos básicos" (ID: 6) // Tema de refuerzo
```

3. **Subir recursos por estilo**:
```php
// Para tema "Conceptos básicos" (ID: 2)
Recursos subidos:
- visual: "diagrama_conceptos.pdf", "infografia_visual.png"
- verbal: "explicacion_detallada.pdf", "audio_conceptos.mp3"  
- activo: "ejercicios_interactivos.html", "simulacion_practica.swf"
- reflexivo: "casos_estudio.pdf", "preguntas_reflexion.pdf"
```

4. **Crear evaluaciones**:
```php
// Examen "Evaluación Conceptos Básicos"
Preguntas:
1. "¿Cuál es la definición correcta de X?"
   - Opciones: A, B, C, D (respuesta correcta: índice 1 = opción B)
2. "¿Qué característica tiene Y?"
   - Opciones: A, B, C, D (respuesta correcta: índice 2 = opción C)
3. "¿Cómo se aplica Z?"
   - Opciones: A, B, C, D (respuesta correcta: índice 0 = opción A)
```

5. **Crear ruta de aprendizaje**:
```php
// Via /path/createsteproute.php
Ruta: "Aprendizaje Progresivo"
Secuencia:
- Paso 1: Tema "Introducción" (recursos)
- Paso 2: Tema "Conceptos básicos" (recursos)
- Paso 3: "Evaluación Conceptos Básicos" (examen)
  * Si aprueba (≥70): ir a "Temas avanzados"
  * Si reprueba (<70): ir a "Refuerzo - Conceptos básicos"
- Paso 4: Tema "Temas avanzados" (recursos)
- Paso 5: "Evaluación Final" (examen)
```

### 20.2. Caso de Uso 2: Experiencia del Estudiante

#### **Escenario**: Estudiante completa el flujo completo del sistema

**Flujo del estudiante**:

1. **Primera visita - Responder encuesta**:
```php
// Estudiante accede a surveyform.php
// Responde 44 preguntas del cuestionario ILS
// Sistema calcula: Estilo dominante = "Visual"
// Se guarda en learningstylesurvey_userstyles
```

2. **Acceder a ruta personalizada**:
```php
// Estudiante hace clic en "Comenzar Ruta Aprendizaje Adaptativa"
// Sistema en vista_estudiante.php:
// 1. Detecta estilo = "Visual"
// 2. Carga ruta del curso
// 3. Inicia progreso en paso 1
// 4. Filtra recursos del primer tema por estilo "visual"
```

3. **Progreso a través de la ruta**:
```php
// Paso 1: Ve recursos visuales de "Introducción"
//   - diagrama_introduccion.png
//   - video_introductorio.mp4
// Estudiante hace clic en "Siguiente"

// Paso 2: Ve recursos visuales de "Conceptos básicos"  
//   - diagrama_conceptos.pdf
//   - infografia_visual.png
// Estudiante hace clic en "Siguiente"

// Paso 3: Toma "Evaluación Conceptos Básicos"
// Resultado: 85/100 (aprueba)
// Sistema automáticamente avanza a "Temas avanzados"

// Paso 4: Ve recursos visuales de "Temas avanzados"
//   - esquemas_avanzados.pdf  
//   - simulacion_visual.html
```

### 20.3. Caso de Uso 3: Estudiante con Dificultades

#### **Escenario**: Estudiante reprueba evaluación y necesita refuerzo

**Flujo de refuerzo**:

```php
// Estudiante reprueba "Evaluación Conceptos Básicos" con 45/100
// Sistema ejecuta lógica en responder_quiz.php:

if ($score < 70 && $current_step->failredirect) {
    // Redirigir a tema de refuerzo (ID: 6)
    $tema_refuerzo = $DB->get_record('learningstylesurvey_temas', 
        ['id' => $current_step->failredirect]);
        
    // En vista_estudiante.php se muestra:
    echo "<div class='alert alert-warning'>";
    echo "Necesitas reforzar los conceptos básicos antes de continuar.";
    echo "</div>";
    
    // Mostrar recursos de refuerzo filtrados por estilo
    $recursos_refuerzo = $DB->get_records('learningstylesurvey_resources', [
        'tema' => 6, // Tema de refuerzo
        'style' => 'visual', // Estilo del estudiante
        'courseid' => $courseid
    ]);
}
```

**Recursos de refuerzo mostrados** (para estudiante visual):
- "conceptos_simplificados.pdf"
- "tutorial_paso_a_paso.png" 
- "video_explicativo_basico.mp4"

**Después del refuerzo**:
- Estudiante puede volver a intentar la evaluación
- Si aprueba en segundo intento, continúa con la ruta normal
- Si sigue reprobando, puede necesitar intervención del profesor

### 20.4. Caso de Uso 4: Profesor Monitoreando Progreso

#### **Escenario**: Profesor revisa el progreso de sus estudiantes

**Consultas que puede realizar**:

```php
// 1. Ver estudiantes que han completado la encuesta
$students_with_style = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, us.style, us.timecreated
    FROM {user} u
    JOIN {learningstylesurvey_userstyles} us ON u.id = us.userid
    JOIN {user_enrolments} ue ON u.id = ue.userid
    JOIN {enrol} e ON ue.enrolid = e.id
    WHERE e.courseid = ?
    ORDER BY us.timecreated DESC
", [$courseid]);

// 2. Ver progreso actual de estudiantes
$students_progress = $DB->get_records_sql("
    SELECT u.firstname, u.lastname, up.status, 
           ls.stepnumber, 
           CASE WHEN ls.istest = 1 THEN 'Examen' ELSE 'Recursos' END as step_type
    FROM {user} u
    JOIN {learningstylesurvey_user_progress} up ON u.id = up.userid
    JOIN {learningpath_steps} ls ON up.current_stepid = ls.id
    JOIN {user_enrolments} ue ON u.id = ue.userid
    JOIN {enrol} e ON ue.enrolid = e.id
    WHERE e.courseid = ? AND up.pathid = ?
    ORDER BY u.lastname, u.firstname
", [$courseid, $pathid]);

// 3. Ver resultados de exámenes
$quiz_results = $DB->get_records_sql("
    SELECT u.firstname, u.lastname, q.name as quiz_name, 
           qr.score, qr.timecompleted,
           CASE WHEN qr.score >= 70 THEN 'Aprobado' ELSE 'Reprobado' END as status
    FROM {learningstylesurvey_quiz_results} qr
    JOIN {user} u ON qr.userid = u.id
    JOIN {learningstylesurvey_quizzes} q ON qr.quizid = q.id
    WHERE qr.courseid = ?
    ORDER BY qr.timecompleted DESC
", [$courseid]);
```

**Dashboard del profesor** (información mostrada):
- 📊 25 estudiantes han completado la encuesta
- 👥 Distribución de estilos: Visual (40%), Verbal (25%), Activo (20%), Reflexivo (15%)
- 📈 Progreso promedio: Paso 3 de 5
- ⚠️ 3 estudiantes necesitan refuerzo en conceptos básicos
- ✅ 18 estudiantes han completado la ruta completa

### 20.5. Caso de Uso 5: Resolución de Problemas Comunes

#### **Problema 1: Estudiante no ve recursos**

**Diagnóstico**:
```php
// Verificar estilo asignado
$user_style = $DB->get_record('learningstylesurvey_userstyles', 
    ['userid' => $student_id]);
// Resultado: style = "Visual"

// Verificar recursos disponibles para el tema/estilo
$resources = $DB->get_records('learningstylesurvey_resources', [
    'tema' => $tema_id,
    'style' => 'visual', // Importante: case sensitive
    'courseid' => $courseid
]);
// Resultado: 0 recursos encontrados
```

**Solución**: 
- Profesor debe subir recursos específicos para estilo "visual" en ese tema
- Verificar que el campo `style` en BD coincida exactamente (case sensitive)

#### **Problema 2: Examen siempre marca respuestas como incorrectas**

**Diagnóstico**:
```php
// Verificar estructura de pregunta
$question = $DB->get_record('learningstylesurvey_questions', ['id' => $question_id]);
// correctanswer = "2" (índice de respuesta correcta)

// Verificar opciones ordenadas
$options = $DB->get_records('learningstylesurvey_options', 
    ['questionid' => $question_id], 'id ASC'); // CRÍTICO: ordenar por id ASC
// Opciones: [0] => "Opción A", [1] => "Opción B", [2] => "Opción C", [3] => "Opción D"

// Verificar lógica de comparación
$selected_index = 2; // Usuario seleccionó opción C
$correct_index = intval($question->correctanswer); // 2
// $selected_index === $correct_index → true → CORRECTO
```

**Solución**:
- Asegurar que `ORDER BY id ASC` se use consistentemente
- Verificar que `correctanswer` use índices basados en 0
- No mezclar sistemas de índices entre creación, edición y evaluación

#### **Problema 3: Estudiante no avanza después de aprobar examen**

**Diagnóstico**:
```php
// Verificar configuración de saltos en learningpath_steps
$step = $DB->get_record('learningpath_steps', ['id' => $step_id]);
// passredirect = NULL (debería tener ID del siguiente tema)
// failredirect = NULL (debería tener ID del tema de refuerzo)
```

**Solución**:
- Profesor debe configurar los saltos en el editor de rutas
- Asegurar que `passredirect` y `failredirect` apunten a IDs válidos de temas

---

## 21. Troubleshooting y Resolución de Problemas

### 21.1. Problemas de Instalación

#### **Error: "Tabla ya existe"**
```
Error message: Table 'mdl_learningstylesurvey' already exists
```

**Causa**: Instalación anterior incompleta o conflicto de versiones

**Solución**:
```sql
-- Opción 1: Limpiar instalación anterior
DROP TABLE IF EXISTS mdl_learningstylesurvey_quiz_results;
DROP TABLE IF EXISTS mdl_learningstylesurvey_options;
DROP TABLE IF EXISTS mdl_learningstylesurvey_questions;
DROP TABLE IF EXISTS mdl_learningstylesurvey_quizzes;
DROP TABLE IF EXISTS mdl_learningstylesurvey_user_progress;
DROP TABLE IF EXISTS mdl_learningpath_steps;
DROP TABLE IF EXISTS mdl_learningstylesurvey_path_temas;
DROP TABLE IF EXISTS mdl_learningstylesurvey_path_evaluations;
DROP TABLE IF EXISTS mdl_learningstylesurvey_path_files;
DROP TABLE IF EXISTS mdl_learningstylesurvey_paths;
DROP TABLE IF EXISTS mdl_learningstylesurvey_resources;
DROP TABLE IF EXISTS mdl_learningstylesurvey_temas;
DROP TABLE IF EXISTS mdl_learningstylesurvey_responses;
DROP TABLE IF EXISTS mdl_learningstylesurvey_userstyles;
DROP TABLE IF EXISTS mdl_learningstylesurvey_results;
DROP TABLE IF EXISTS mdl_learningstylesurvey_learningpath;
DROP TABLE IF EXISTS mdl_learningstylesurvey_inforoute;
DROP TABLE IF EXISTS mdl_learningstylesurvey;

-- Luego reinstalar el plugin
```

#### **Error: "Cannot create directory"**
```
Error message: Cannot create upload directory /path/to/moodledata/learningstylesurvey/
```

**Causa**: Permisos insuficientes en el directorio dataroot

**Solución**:
```bash
# Verificar permisos de dataroot
ls -la /path/to/moodledata/

# Crear directorio manualmente
mkdir -p /path/to/moodledata/learningstylesurvey/
chown -R www-data:www-data /path/to/moodledata/learningstylesurvey/
chmod -R 755 /path/to/moodledata/learningstylesurvey/
```

### 21.2. Problemas de Funcionalidad

#### **Problema: "No se detecta estilo de aprendizaje"**

**Síntomas**:
- Estudiante completa encuesta pero no se asigna estilo
- Error: "No se ha detectado un estilo de aprendizaje"

**Diagnóstico**:
```php
// Verificar respuestas guardadas
$responses = $DB->get_records('learningstylesurvey_responses', 
    ['userid' => $user_id, 'surveyid' => $survey_id]);
echo "Respuestas encontradas: " . count($responses);

// Verificar cálculo de estilos
foreach ($responses as $response) {
    echo "Pregunta {$response->questionid}: {$response->response}";
}

// Verificar función de cálculo
$stylecounts = learningstylesurvey_calculate_styles($responses);
var_dump($stylecounts);
```

**Soluciones posibles**:
1. **Respuestas incompletas**: Verificar que las 44 preguntas tengan respuesta
2. **Error en cálculo**: Revisar función `learningstylesurvey_calculate_styles()`
3. **Timeout de sesión**: Aumentar `session.gc_maxlifetime` en PHP

#### **Problema: "Recursos no aparecen para el estudiante"**

**Síntomas**:
- Estudiante accede a la ruta pero no ve recursos
- Mensaje: "No hay recursos disponibles para tu estilo"

**Diagnóstico paso a paso**:
```php
// 1. Verificar estilo del usuario
$user_style = $DB->get_record('learningstylesurvey_userstyles', ['userid' => $user_id]);
echo "Estilo detectado: " . ($user_style ? $user_style->style : 'NO DETECTADO');

// 2. Verificar tema actual
$progress = $DB->get_record('learningstylesurvey_user_progress', 
    ['userid' => $user_id, 'pathid' => $path_id]);
$current_step = $DB->get_record('learningpath_steps', ['id' => $progress->current_stepid]);
echo "Tema actual: " . $current_step->resourceid;

// 3. Verificar recursos disponibles
$resources = $DB->get_records('learningstylesurvey_resources', [
    'tema' => $current_step->resourceid,
    'style' => strtolower(trim($user_style->style)),
    'courseid' => $course_id
]);
echo "Recursos encontrados: " . count($resources);

// 4. Verificar recursos sin filtro de estilo
$all_resources = $DB->get_records('learningstylesurvey_resources', [
    'tema' => $current_step->resourceid,
    'courseid' => $course_id
]);
echo "Total recursos del tema: " . count($all_resources);
```

**Soluciones**:
1. **Normalizar estilos**: `strtolower(trim($style))` en todas las comparaciones
2. **Verificar campo tema**: Usar `tema` no `temaid` en consultas
3. **Subir recursos**: Profesor debe subir recursos para ese estilo específico

#### **Problema: "Evaluaciones no califican correctamente"**

**Síntomas**:
- Todas las respuestas se marcan como incorrectas
- Puntuación siempre 0/100 aunque respuestas sean correctas

**Diagnóstico detallado**:
```php
// Verificar pregunta específica
$question = $DB->get_record('learningstylesurvey_questions', ['id' => $question_id]);
echo "Respuesta correcta almacenada: " . $question->correctanswer;

// Verificar opciones ordenadas
$options = $DB->get_records('learningstylesurvey_options', 
    ['questionid' => $question_id], 'id ASC');
$i = 0;
foreach ($options as $option) {
    echo "Índice {$i}: {$option->optiontext} (ID: {$option->id})";
    $i++;
}

// Verificar respuesta del usuario
$user_option_id = $_POST["question_{$question_id}"];
echo "Usuario seleccionó opción ID: " . $user_option_id;

// Verificar lógica de comparación
$option_index = 0;
foreach ($options as $opt) {
    if ($opt->id == $user_option_id) {
        echo "Índice seleccionado: " . $option_index;
        echo "Índice correcto: " . intval($question->correctanswer);
        echo "¿Coinciden? " . ($option_index == intval($question->correctanswer) ? 'SÍ' : 'NO');
        break;
    }
    $option_index++;
}
```

**Soluciones críticas**:
1. **Ordenamiento consistente**: Siempre `ORDER BY id ASC` para opciones
2. **Índices desde 0**: Respuestas correctas deben usar índices 0, 1, 2, 3
3. **No mezclar sistemas**: Mantener consistencia entre creación, edición y evaluación

### 21.3. Problemas de Rendimiento

#### **Problema: "Página de estudiante carga lentamente"**

**Causa**: Consultas ineficientes o muchas consultas N+1

**Optimización**:
```php
// ❌ MALO: Múltiples consultas
foreach ($steps as $step) {
    $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $step->resourceid]);
    $quiz = $DB->get_record('learningstylesurvey_quizzes', ['id' => $step->resourceid]);
}

// ✅ BUENO: Una sola consulta JOIN
$steps_with_content = $DB->get_records_sql("
    SELECT s.*, 
           r.name as resource_name, r.filename,
           q.name as quiz_name
    FROM {learningpath_steps} s
    LEFT JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id AND s.istest = 0
    LEFT JOIN {learningstylesurvey_quizzes} q ON s.resourceid = q.id AND s.istest = 1
    WHERE s.pathid = ?
    ORDER BY s.stepnumber
", [$pathid]);
```

#### **Problema: "Subida de archivos falla"**

**Síntomas**:
- Error de timeout al subir archivos grandes
- Archivo se corta parcialmente

**Soluciones**:
```php
// Configuración PHP recomendada
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Validación adicional en uploadresource.php
if ($_FILES['resource_file']['size'] > 50 * 1024 * 1024) {
    throw new moodle_exception('Archivo demasiado grande. Máximo 50MB.');
}

if ($_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
    switch ($_FILES['resource_file']['error']) {
        case UPLOAD_ERR_INI_SIZE:
            throw new moodle_exception('Archivo excede upload_max_filesize');
        case UPLOAD_ERR_FORM_SIZE:
            throw new moodle_exception('Archivo excede MAX_FILE_SIZE');
        case UPLOAD_ERR_PARTIAL:
            throw new moodle_exception('Archivo subido parcialmente');
        default:
            throw new moodle_exception('Error desconocido en subida');
    }
}
```

### 21.4. Problemas de Navegación

#### **Problema: "Estudiante queda atascado en un paso"**

**Síntomas**:
- Botón "Siguiente" no aparece o no funciona
- Progreso no se actualiza

**Diagnóstico**:
```php
// Verificar paso actual
$progress = $DB->get_record('learningstylesurvey_user_progress', 
    ['userid' => $user_id, 'pathid' => $path_id]);
echo "Paso actual ID: " . $progress->current_stepid;

// Verificar si existe siguiente paso
$current_step = $DB->get_record('learningpath_steps', ['id' => $progress->current_stepid]);
$next_step = $DB->get_record('learningpath_steps', [
    'pathid' => $path_id,
    'stepnumber' => $current_step->stepnumber + 1
]);
echo "¿Existe siguiente paso? " . ($next_step ? 'SÍ' : 'NO');

// Verificar si es examen y requiere aprobar
if ($current_step->istest) {
    $last_result = $DB->get_record('learningstylesurvey_quiz_results', 
        ['userid' => $user_id, 'quizid' => $current_step->resourceid], 
        '*', IGNORE_MULTIPLE);
    echo "Último resultado: " . ($last_result ? $last_result->score : 'NO TOMADO');
}
```

**Soluciones**:
1. **Completar examen**: Si es paso de examen, debe tomarlo primero
2. **Aprobar examen**: Si reprobó, debe ir a refuerzo antes de continuar
3. **Configurar ruta**: Verificar que haya pasos siguientes configurados

### 21.5. Herramientas de Diagnóstico

#### **Script de diagnóstico completo**:

```php
// utils/diagnostic.php
<?php
require_once('../../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);

echo "<h1>🔍 Diagnóstico Completo</h1>";

// 1. Verificar tablas
echo "<h2>📋 Verificación de Tablas</h2>";
$required_tables = [
    'learningstylesurvey', 'learningstylesurvey_userstyles',
    'learningstylesurvey_paths', 'learningpath_steps',
    'learningstylesurvey_resources', 'learningstylesurvey_temas',
    'learningstylesurvey_quizzes', 'learningstylesurvey_questions',
    'learningstylesurvey_options', 'learningstylesurvey_quiz_results'
];

foreach ($required_tables as $table) {
    if ($DB->get_manager()->table_exists($table)) {
        $count = $DB->count_records($table, ['courseid' => $courseid]);
        echo "✅ {$table}: {$count} registros<br>";
    } else {
        echo "❌ {$table}: NO EXISTE<br>";
    }
}

// 2. Verificar usuarios con estilo
echo "<h2>👥 Usuarios con Estilo Detectado</h2>";
$users_with_style = $DB->get_records_sql("
    SELECT u.firstname, u.lastname, us.style, us.timecreated
    FROM {user} u
    JOIN {learningstylesurvey_userstyles} us ON u.id = us.userid
    JOIN {user_enrolments} ue ON u.id = ue.userid
    JOIN {enrol} e ON ue.enrolid = e.id
    WHERE e.courseid = ?
", [$courseid]);

foreach ($users_with_style as $user) {
    echo "👤 {$user->firstname} {$user->lastname}: {$user->style}<br>";
}

// 3. Verificar rutas configuradas
echo "<h2>🛤️ Rutas Configuradas</h2>";
$paths = $DB->get_records('learningstylesurvey_paths', ['courseid' => $courseid]);
foreach ($paths as $path) {
    $step_count = $DB->count_records('learningpath_steps', ['pathid' => $path->id]);
    echo "📍 {$path->name}: {$step_count} pasos<br>";
}

// 4. Verificar recursos por estilo
echo "<h2>📁 Recursos por Estilo</h2>";
$resource_stats = $DB->get_records_sql("
    SELECT style, COUNT(*) as count
    FROM {learningstylesurvey_resources}
    WHERE courseid = ?
    GROUP BY style
", [$courseid]);

foreach ($resource_stats as $stat) {
    echo "📄 Estilo {$stat->style}: {$stat->count} recursos<br>";
}

// 5. Verificar problemas comunes
echo "<h2>⚠️ Problemas Detectados</h2>";

// Usuarios sin estilo que deberían tenerlo
$users_without_style = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {user_enrolments} ue ON u.id = ue.userid
    JOIN {enrol} e ON ue.enrolid = e.id
    LEFT JOIN {learningstylesurvey_userstyles} us ON u.id = us.userid
    WHERE e.courseid = ? AND us.id IS NULL AND u.id > 2
", [$courseid]);

if (!empty($users_without_style)) {
    echo "⚠️ Usuarios sin estilo detectado:<br>";
    foreach ($users_without_style as $user) {
        echo "- {$user->firstname} {$user->lastname}<br>";
    }
}

// Temas sin recursos
$temas_without_resources = $DB->get_records_sql("
    SELECT t.id, t.tema
    FROM {learningstylesurvey_temas} t
    LEFT JOIN {learningstylesurvey_resources} r ON t.id = r.tema
    WHERE t.courseid = ? AND r.id IS NULL
", [$courseid]);

if (!empty($temas_without_resources)) {
    echo "⚠️ Temas sin recursos:<br>";
    foreach ($temas_without_resources as $tema) {
        echo "- {$tema->tema}<br>";
    }
}

echo "<h2>✅ Diagnóstico Completado</h2>";
?>
```

---

## 22. Documentación para Desarrolladores y APIs

### 22.1. APIs Internas del Plugin

#### **API de Estilos de Aprendizaje**

```php
/**
 * API completa para gestión de estilos de aprendizaje
 */
class learningstylesurvey_styles_api {
    
    /**
     * Detecta el estilo de aprendizaje basado en respuestas ILS
     * @param array $responses Array asociativo [pregunta_id => respuesta_value]
     * @return array ['style' => string, 'scores' => array, 'confidence' => float]
     */
    public static function detect_learning_style($responses) {
        $stylecounts = [
            'Activo' => 0, 'Reflexivo' => 0,
            'Sensorial' => 0, 'Intuitivo' => 0,
            'Visual' => 0, 'Verbal' => 0,
            'Secuencial' => 0, 'Global' => 0
        ];
        
        // Mapeo de preguntas ILS a dimensiones
        $question_mapping = self::get_ils_mapping();
        
        foreach ($responses as $question_num => $response) {
            if (isset($question_mapping[$question_num])) {
                $styles = $question_mapping[$question_num];
                $selected_style = $styles[intval($response)];
                $stylecounts[$selected_style]++;
            }
        }
        
        // Calcular estilo dominante y confianza
        $dominant_style = self::calculate_dominant_style($stylecounts);
        $confidence = self::calculate_confidence($stylecounts);
        
        return [
            'style' => $dominant_style,
            'scores' => $stylecounts,
            'confidence' => $confidence
        ];
    }
    
    /**
     * Obtiene el mapeo completo de preguntas ILS
     * @return array Mapeo [pregunta_id => [estilo_opcion_0, estilo_opcion_1]]
     */
    private static function get_ils_mapping() {
        return [
            // Dimensión Activo/Reflexivo
            1 => ['Activo', 'Reflexivo'], 5 => ['Reflexivo', 'Activo'],
            9 => ['Activo', 'Reflexivo'], 13 => ['Activo', 'Reflexivo'],
            17 => ['Reflexivo', 'Activo'], 21 => ['Activo', 'Reflexivo'],
            25 => ['Reflexivo', 'Activo'], 29 => ['Activo', 'Reflexivo'],
            33 => ['Reflexivo', 'Activo'], 37 => ['Activo', 'Reflexivo'],
            41 => ['Activo', 'Reflexivo'],
            
            // Dimensión Sensorial/Intuitivo
            2 => ['Sensorial', 'Intuitivo'], 6 => ['Sensorial', 'Intuitivo'],
            10 => ['Intuitivo', 'Sensorial'], 14 => ['Sensorial', 'Intuitivo'],
            18 => ['Sensorial', 'Intuitivo'], 22 => ['Intuitivo', 'Sensorial'],
            26 => ['Sensorial', 'Intuitivo'], 30 => ['Sensorial', 'Intuitivo'],
            34 => ['Intuitivo', 'Sensorial'], 38 => ['Sensorial', 'Intuitivo'],
            42 => ['Intuitivo', 'Sensorial'],
            
            // Dimensión Visual/Verbal
            3 => ['Visual', 'Verbal'], 7 => ['Visual', 'Verbal'],
            11 => ['Visual', 'Verbal'], 15 => ['Verbal', 'Visual'],
            19 => ['Visual', 'Verbal'], 23 => ['Visual', 'Verbal'],
            27 => ['Verbal', 'Visual'], 31 => ['Visual', 'Verbal'],
            35 => ['Verbal', 'Visual'], 39 => ['Verbal', 'Visual'],
            43 => ['Visual', 'Verbal'],
            
            // Dimensión Secuencial/Global
            4 => ['Secuencial', 'Global'], 8 => ['Secuencial', 'Global'],
            12 => ['Global', 'Secuencial'], 16 => ['Secuencial', 'Global'],
            20 => ['Global', 'Secuencial'], 24 => ['Secuencial', 'Global'],
            28 => ['Global', 'Secuencial'], 32 => ['Secuencial', 'Global'],
            36 => ['Global', 'Secuencial'], 40 => ['Secuencial', 'Global'],
            44 => ['Secuencial', 'Global']
        ];
    }
    
    /**
     * Calcula el estilo dominante
     * @param array $stylecounts Conteos por estilo
     * @return string Estilo dominante
     */
    private static function calculate_dominant_style($stylecounts) {
        // Calcular diferencias por dimensión
        $dimensions = [
            'Activo/Reflexivo' => $stylecounts['Activo'] - $stylecounts['Reflexivo'],
            'Sensorial/Intuitivo' => $stylecounts['Sensorial'] - $stylecounts['Intuitivo'],
            'Visual/Verbal' => $stylecounts['Visual'] - $stylecounts['Verbal'],
            'Secuencial/Global' => $stylecounts['Secuencial'] - $stylecounts['Global']
        ];
        
        // Encontrar dimensión con mayor diferencia absoluta
        $max_diff = 0;
        $dominant_dimension = '';
        
        foreach ($dimensions as $dim => $diff) {
            if (abs($diff) > $max_diff) {
                $max_diff = abs($diff);
                $dominant_dimension = $dim;
            }
        }
        
        // Retornar estilo específico
        switch ($dominant_dimension) {
            case 'Activo/Reflexivo':
                return $dimensions[$dominant_dimension] > 0 ? 'Activo' : 'Reflexivo';
            case 'Sensorial/Intuitivo':
                return $dimensions[$dominant_dimension] > 0 ? 'Sensorial' : 'Intuitivo';
            case 'Visual/Verbal':
                return $dimensions[$dominant_dimension] > 0 ? 'Visual' : 'Verbal';
            case 'Secuencial/Global':
                return $dimensions[$dominant_dimension] > 0 ? 'Secuencial' : 'Global';
            default:
                return 'Visual'; // Estilo por defecto
        }
    }
    
    /**
     * Calcula el nivel de confianza del estilo detectado (0-1)
     * @param array $stylecounts Conteos por estilo
     * @return float Confianza entre 0 y 1
     */
    private static function calculate_confidence($stylecounts) {
        $total_questions = 44;
        $max_possible_diff = 11; // Máxima diferencia por dimensión
        
        // Calcular diferencias
        $diffs = [
            abs($stylecounts['Activo'] - $stylecounts['Reflexivo']),
            abs($stylecounts['Sensorial'] - $stylecounts['Intuitivo']),
            abs($stylecounts['Visual'] - $stylecounts['Verbal']),
            abs($stylecounts['Secuencial'] - $stylecounts['Global'])
        ];
        
        $max_diff = max($diffs);
        return min($max_diff / $max_possible_diff, 1.0);
    }
}
```

#### **API de Rutas de Aprendizaje**

```php
/**
 * API para gestión de rutas de aprendizaje adaptativas
 */
class learningstylesurvey_paths_api {
    
    /**
     * Crea una nueva ruta de aprendizaje
     * @param int $courseid ID del curso
     * @param int $userid ID del creador
     * @param string $name Nombre de la ruta
     * @param array $config Configuración de la ruta
     * @return int ID de la ruta creada
     */
    public static function create_learning_path($courseid, $userid, $name, $config = []) {
        global $DB;
        
        $path = new stdClass();
        $path->courseid = $courseid;
        $path->userid = $userid;
        $path->name = $name;
        $path->timecreated = time();
        
        // Obtener cmid si está disponible
        $cmid = self::get_course_module_id($courseid);
        if ($cmid) {
            $path->cmid = $cmid;
        }
        
        return $DB->insert_record('learningstylesurvey_paths', $path);
    }
    
    /**
     * Añade un paso a una ruta existente
     * @param int $pathid ID de la ruta
     * @param array $step_config Configuración del paso
     * @return int ID del paso creado
     */
    public static function add_step_to_path($pathid, $step_config) {
        global $DB;
        
        // Obtener siguiente número de paso
        $last_step = $DB->get_record_sql("
            SELECT MAX(stepnumber) as max_step
            FROM {learningpath_steps}
            WHERE pathid = ?
        ", [$pathid]);
        
        $step = new stdClass();
        $step->pathid = $pathid;
        $step->stepnumber = ($last_step->max_step ?? 0) + 1;
        $step->resourceid = $step_config['resourceid'];
        $step->istest = $step_config['istest'] ?? 0;
        $step->passredirect = $step_config['passredirect'] ?? null;
        $step->failredirect = $step_config['failredirect'] ?? null;
        
        return $DB->insert_record('learningpath_steps', $step);
    }
    
    /**
     * Obtiene el progreso de un usuario en una ruta
     * @param int $userid ID del usuario
     * @param int $pathid ID de la ruta
     * @return object|false Objeto progreso o false
     */
    public static function get_user_progress($userid, $pathid) {
        global $DB;
        
        return $DB->get_record('learningstylesurvey_user_progress', [
            'userid' => $userid,
            'pathid' => $pathid
        ]);
    }
    
    /**
     * Actualiza el progreso de un usuario
     * @param int $userid ID del usuario
     * @param int $pathid ID de la ruta
     * @param int $current_stepid ID del paso actual
     * @param string $status Estado del progreso
     * @return bool Éxito de la operación
     */
    public static function update_user_progress($userid, $pathid, $current_stepid, $status = 'inprogress') {
        global $DB;
        
        $progress = self::get_user_progress($userid, $pathid);
        
        if ($progress) {
            $progress->current_stepid = $current_stepid;
            $progress->status = $status;
            $progress->timemodified = time();
            return $DB->update_record('learningstylesurvey_user_progress', $progress);
        } else {
            $new_progress = new stdClass();
            $new_progress->userid = $userid;
            $new_progress->pathid = $pathid;
            $new_progress->current_stepid = $current_stepid;
            $new_progress->status = $status;
            $new_progress->timemodified = time();
            return $DB->insert_record('learningstylesurvey_user_progress', $new_progress);
        }
    }
    
    /**
     * Determina el siguiente paso basado en resultado de evaluación
     * @param object $current_step Paso actual
     * @param int $score Puntuación obtenida
     * @param int $passing_score Puntuación mínima para aprobar
     * @return int|null ID del siguiente paso o null
     */
    public static function get_next_step($current_step, $score, $passing_score = 70) {
        if ($current_step->istest) {
            if ($score >= $passing_score && $current_step->passredirect) {
                return $current_step->passredirect;
            } elseif ($score < $passing_score && $current_step->failredirect) {
                return $current_step->failredirect;
            }
        }
        
        // Para recursos normales, seguir secuencia
        global $DB;
        $next_step = $DB->get_record('learningpath_steps', [
            'pathid' => $current_step->pathid,
            'stepnumber' => $current_step->stepnumber + 1
        ]);
        
        return $next_step ? $next_step->id : null;
    }
    
    /**
     * Obtiene Course Module ID para un curso
     * @param int $courseid ID del curso
     * @return int|null Course Module ID o null
     */
    private static function get_course_module_id($courseid) {
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'learningstylesurvey') {
                return $cm->id;
            }
        }
        return null;
    }
}
```

#### **API de Recursos**

```php
/**
 * API para gestión de recursos didácticos
 */
class learningstylesurvey_resources_api {
    
    /**
     * Obtiene recursos filtrados por estilo y tema
     * @param int $courseid ID del curso
     * @param string $style Estilo de aprendizaje
     * @param int $tema_id ID del tema
     * @return array Array de recursos
     */
    public static function get_filtered_resources($courseid, $style, $tema_id) {
        global $DB;
        
        return $DB->get_records('learningstylesurvey_resources', [
            'courseid' => $courseid,
            'style' => strtolower(trim($style)),
            'tema' => $tema_id
        ]);
    }
    
    /**
     * Sube un nuevo recurso
     * @param array $file_data Datos del archivo ($_FILES)
     * @param array $resource_data Metadatos del recurso
     * @return int ID del recurso creado
     */
    public static function upload_resource($file_data, $resource_data) {
        global $DB, $USER;
        
        // Validar archivo
        self::validate_file($file_data);
        
        // Crear directorio si no existe
        $upload_dir = learningstylesurvey_ensure_upload_directory($resource_data['courseid']);
        
        // Generar nombre único
        $file_extension = pathinfo($file_data['name'], PATHINFO_EXTENSION);
        $unique_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $final_path = $upload_dir . $unique_filename;
        
        // Mover archivo
        if (!move_uploaded_file($file_data['tmp_name'], $final_path)) {
            throw new moodle_exception('uploadfailed', 'learningstylesurvey');
        }
        
        // Guardar en BD
        $record = new stdClass();
        $record->courseid = $resource_data['courseid'];
        $record->name = $resource_data['name'];
        $record->filename = $unique_filename;
        $record->style = strtolower($resource_data['style']);
        $record->tema = $resource_data['tema'];
        $record->userid = $USER->id;
        $record->timecreated = time();
        
        return $DB->insert_record('learningstylesurvey_resources', $record);
    }
    
    /**
     * Valida un archivo subido
     * @param array $file_data Datos del archivo
     * @throws moodle_exception Si el archivo no es válido
     */
    private static function validate_file($file_data) {
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 
                         'doc', 'docx', 'ppt', 'pptx', 'txt', 'html'];
        
        if ($file_data['error'] !== UPLOAD_ERR_OK) {
            throw new moodle_exception('uploaderror', 'learningstylesurvey');
        }
        
        $extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_types)) {
            throw new moodle_exception('invalidfiletype', 'learningstylesurvey');
        }
        
        if ($file_data['size'] > 50 * 1024 * 1024) { // 50MB
            throw new moodle_exception('filetoobig', 'learningstylesurvey');
        }
    }
}
```

### 22.2. Hooks y Eventos de Moodle

#### **Eventos del Plugin**

```php
/**
 * Definición de eventos personalizados
 */

// events.php
$observers = [
    [
        'eventname' => '\mod_learningstylesurvey\event\style_detected',
        'callback' => '\mod_learningstylesurvey\observer::style_detected',
    ],
    [
        'eventname' => '\mod_learningstylesurvey\event\quiz_completed',
        'callback' => '\mod_learningstylesurvey\observer::quiz_completed',
    ],
    [
        'eventname' => '\mod_learningstylesurvey\event\path_completed',
        'callback' => '\mod_learningstylesurvey\observer::path_completed',
    ]
];

// classes/event/style_detected.php
namespace mod_learningstylesurvey\event;

class style_detected extends \core\event\base {
    
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'learningstylesurvey_userstyles';
    }
    
    public static function get_name() {
        return get_string('eventstyledetected', 'learningstylesurvey');
    }
    
    public function get_description() {
        return "El usuario {$this->userid} completó la encuesta y se detectó estilo '{$this->other['style']}'.";
    }
    
    /**
     * Crear evento de estilo detectado
     */
    public static function create_from_style($style_record) {
        $event = self::create([
            'objectid' => $style_record->id,
            'userid' => $style_record->userid,
            'other' => ['style' => $style_record->style]
        ]);
        return $event;
    }
}

// classes/observer.php
namespace mod_learningstylesurvey;

class observer {
    
    /**
     * Observador para cuando se detecta un estilo
     */
    public static function style_detected(\mod_learningstylesurvey\event\style_detected $event) {
        global $DB;
        
        // Ejemplo: Enviar notificación al profesor
        $user = $DB->get_record('user', ['id' => $event->userid]);
        $style = $event->other['style'];
        
        // Lógica personalizada aquí
        error_log("Estilo detectado para {$user->firstname}: {$style}");
    }
    
    /**
     * Observador para cuando se completa un quiz
     */
    public static function quiz_completed(\mod_learningstylesurvey\event\quiz_completed $event) {
        // Lógica para procesar completación de quiz
        // Ejemplo: Analíticas, notificaciones, etc.
    }
}
```

### 22.3. Seguridad y Permisos

#### **Validación de Entrada**

```php
/**
 * Funciones de seguridad y validación
 */
class learningstylesurvey_security {
    
    /**
     * Valida y sanitiza parámetros de entrada
     */
    public static function validate_course_access($courseid, $required_capability = 'mod/learningstylesurvey:view') {
        global $USER;
        
        // Verificar que el curso existe
        $course = get_course($courseid);
        if (!$course) {
            throw new moodle_exception('invalidcourseid');
        }
        
        // Verificar contexto y permisos
        $context = context_course::instance($courseid);
        require_capability($required_capability, $context);
        
        // Verificar inscripción
        if (!is_enrolled($context, $USER->id)) {
            throw new moodle_exception('notenrolled', 'learningstylesurvey');
        }
        
        return $course;
    }
    
    /**
     * Valida que un usuario puede acceder a un recurso específico
     */
    public static function validate_resource_access($resource_id, $userid) {
        global $DB;
        
        $resource = $DB->get_record('learningstylesurvey_resources', ['id' => $resource_id]);
        if (!$resource) {
            throw new moodle_exception('invalidresource', 'learningstylesurvey');
        }
        
        // Verificar acceso al curso
        self::validate_course_access($resource->courseid);
        
        return $resource;
    }
    
    /**
     * Sanitiza datos de subida de archivos
     */
    public static function sanitize_upload_data($data) {
        return [
            'name' => clean_param($data['name'], PARAM_TEXT),
            'style' => clean_param($data['style'], PARAM_ALPHA),
            'tema' => clean_param($data['tema'], PARAM_INT),
            'courseid' => clean_param($data['courseid'], PARAM_INT)
        ];
    }
    
    /**
     * Previene ataques de path traversal en nombres de archivo
     */
    public static function sanitize_filename($filename) {
        // Remover caracteres peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevenir path traversal
        $filename = str_replace(['../', '.\\', '..\\'], '', $filename);
        
        // Limitar longitud
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
            $filename = $basename . '.' . $extension;
        }
        
        return $filename;
    }
}
```

#### **Protección CSRF**

```php
/**
 * Implementación de protección CSRF
 */

// En formularios importantes
function render_secure_form($action_url, $form_data) {
    $sesskey = sesskey(); // Token CSRF de Moodle
    
    echo "<form method='post' action='{$action_url}'>";
    echo "<input type='hidden' name='sesskey' value='{$sesskey}'>";
    
    foreach ($form_data as $field) {
        echo $field;
    }
    
    echo "<button type='submit'>Enviar</button>";
    echo "</form>";
}

// En procesamiento de formularios
function process_secure_form() {
    require_sesskey(); // Valida token CSRF
    
    // Procesar datos del formulario
}
```

### 22.4. Optimización y Performance

#### **Caché de Consultas**

```php
/**
 * Sistema de caché para consultas frecuentes
 */
class learningstylesurvey_cache {
    
    /**
     * Obtiene estilo de usuario con caché
     */
    public static function get_user_style($userid) {
        $cache = cache::make('mod_learningstylesurvey', 'userstyles');
        
        $style = $cache->get($userid);
        if ($style === false) {
            global $DB;
            $record = $DB->get_record('learningstylesurvey_userstyles', 
                ['userid' => $userid], '*', IGNORE_MULTIPLE);
            
            $style = $record ? $record->style : null;
            $cache->set($userid, $style);
        }
        
        return $style;
    }
    
    /**
     * Invalida caché de usuario
     */
    public static function invalidate_user_cache($userid) {
        $cache = cache::make('mod_learningstylesurvey', 'userstyles');
        $cache->delete($userid);
    }
}

// Definición de caché en db/caches.php
$definitions = [
    'userstyles' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600, // 1 hora
    ],
    'pathsteps' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl' => 1800, // 30 minutos
    ]
];
```

#### **Optimización de Consultas**

```php
/**
 * Consultas optimizadas para rendimiento
 */
class learningstylesurvey_queries {
    
    /**
     * Obtiene toda la información de ruta en una consulta
     */
    public static function get_path_with_steps($pathid, $userid = null) {
        global $DB;
        
        $sql = "
            SELECT p.id as path_id, p.name as path_name,
                   s.id as step_id, s.stepnumber, s.resourceid, s.istest,
                   s.passredirect, s.failredirect,
                   CASE 
                       WHEN s.istest = 1 THEN q.name 
                       ELSE r.name 
                   END as resource_name,
                   CASE 
                       WHEN s.istest = 1 THEN 'quiz'
                       ELSE 'resource'
                   END as resource_type
            FROM {learningstylesurvey_paths} p
            JOIN {learningpath_steps} s ON p.id = s.pathid
            LEFT JOIN {learningstylesurvey_quizzes} q ON s.resourceid = q.id AND s.istest = 1
            LEFT JOIN {learningstylesurvey_resources} r ON s.resourceid = r.id AND s.istest = 0
            WHERE p.id = ?
            ORDER BY s.stepnumber
        ";
        
        $records = $DB->get_records_sql($sql, [$pathid]);
        
        if (empty($records)) {
            return null;
        }
        
        // Estructurar datos
        $first_record = reset($records);
        $path = [
            'id' => $first_record->path_id,
            'name' => $first_record->path_name,
            'steps' => []
        ];
        
        foreach ($records as $record) {
            $path['steps'][] = [
                'id' => $record->step_id,
                'stepnumber' => $record->stepnumber,
                'resourceid' => $record->resourceid,
                'istest' => $record->istest,
                'passredirect' => $record->passredirect,
                'failredirect' => $record->failredirect,
                'resource_name' => $record->resource_name,
                'resource_type' => $record->resource_type
            ];
        }
        
        // Si se proporciona userid, incluir progreso
        if ($userid) {
            $progress = $DB->get_record('learningstylesurvey_user_progress', 
                ['userid' => $userid, 'pathid' => $pathid]);
            $path['user_progress'] = $progress;
        }
        
        return $path;
    }
}
```

### 22.5. Testing y Debugging

#### **Unit Tests**

```php
/**
 * Tests unitarios para el plugin
 */

// tests/styles_test.php
class mod_learningstylesurvey_styles_testcase extends advanced_testcase {
    
    protected function setUp(): void {
        $this->resetAfterTest();
    }
    
    /**
     * Test del algoritmo de detección de estilos
     */
    public function test_style_detection_algorithm() {
        // Crear respuestas de prueba que deberían resultar en estilo "Visual"
        $responses = [];
        
        // Responder Visual (opción 0) en todas las preguntas Visual/Verbal
        $visual_questions = [3, 7, 11, 19, 23, 31, 43];
        foreach ($visual_questions as $q) {
            $responses[$q] = 0; // Visual
        }
        
        // Responder Verbal (opción 1) en preguntas invertidas
        $verbal_questions = [15, 27, 35, 39];
        foreach ($verbal_questions as $q) {
            $responses[$q] = 0; // Visual (porque están invertidas)
        }
        
        // Completar con respuestas neutras para otras dimensiones
        for ($i = 1; $i <= 44; $i++) {
            if (!isset($responses[$i])) {
                $responses[$i] = 0;
            }
        }
        
        $result = learningstylesurvey_styles_api::detect_learning_style($responses);
        
        $this->assertEquals('Visual', $result['style']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }
    
    /**
     * Test de creación de ruta
     */
    public function test_path_creation() {
        global $DB;
        
        // Crear datos de prueba
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        
        // Crear ruta
        $pathid = learningstylesurvey_paths_api::create_learning_path(
            $course->id, 
            $user->id, 
            'Ruta de Prueba'
        );
        
        // Verificar que se creó
        $this->assertIsInt($pathid);
        $path = $DB->get_record('learningstylesurvey_paths', ['id' => $pathid]);
        $this->assertNotEmpty($path);
        $this->assertEquals('Ruta de Prueba', $path->name);
    }
}
```

#### **Debugging Tools**

```php
/**
 * Herramientas de debugging para desarrollo
 */
class learningstylesurvey_debug {
    
    /**
     * Log de debug con contexto
     */
    public static function log($message, $context = [], $level = 'INFO') {
        if (debugging()) {
            $log_entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ];
            
            error_log('LSS_DEBUG: ' . json_encode($log_entry));
        }
    }
    
    /**
     * Dump de variable formateado
     */
    public static function dump($var, $label = '') {
        if (debugging()) {
            echo "<pre style='background:#f5f5f5; padding:10px; margin:10px; border:1px solid #ddd;'>";
            if ($label) {
                echo "<strong>{$label}:</strong>\n";
            }
            print_r($var);
            echo "</pre>";
        }
    }
    
    /**
     * Medición de performance
     */
    public static function time_start($label) {
        if (debugging()) {
            global $learningstylesurvey_timers;
            $learningstylesurvey_timers[$label] = microtime(true);
        }
    }
    
    public static function time_end($label) {
        if (debugging()) {
            global $learningstylesurvey_timers;
            if (isset($learningstylesurvey_timers[$label])) {
                $elapsed = microtime(true) - $learningstylesurvey_timers[$label];
                self::log("Timer {$label}: {$elapsed}s", [], 'PERFORMANCE');
            }
        }
    }
}

// Uso en código:
// learningstylesurvey_debug::time_start('quiz_calculation');
// // ... código a medir ...
// learningstylesurvey_debug::time_end('quiz_calculation');
```

### 22.6. Extensiones y Personalización

#### **Framework de Plugins**

```php
/**
 * Sistema para extender funcionalidad mediante plugins
 */
interface learningstylesurvey_extension_interface {
    
    /**
     * Procesar estilo detectado
     * @param string $style Estilo detectado
     * @param int $userid ID del usuario
     * @param array $scores Puntuaciones por estilo
     */
    public function process_detected_style($style, $userid, $scores);
    
    /**
     * Filtrar recursos para un usuario
     * @param array $resources Recursos originales
     * @param string $user_style Estilo del usuario
     * @param int $userid ID del usuario
     * @return array Recursos filtrados
     */
    public function filter_resources($resources, $user_style, $userid);
}

/**
 * Gestor de extensiones
 */
class learningstylesurvey_extension_manager {
    
    private static $extensions = [];
    
    /**
     * Registra una extensión
     */
    public static function register_extension($name, $extension) {
        if ($extension instanceof learningstylesurvey_extension_interface) {
            self::$extensions[$name] = $extension;
        }
    }
    
    /**
     * Ejecuta hook de estilo detectado
     */
    public static function trigger_style_detected($style, $userid, $scores) {
        foreach (self::$extensions as $extension) {
            $extension->process_detected_style($style, $userid, $scores);
        }
    }
    
    /**
     * Ejecuta hook de filtrado de recursos
     */
    public static function trigger_filter_resources($resources, $user_style, $userid) {
        $filtered_resources = $resources;
        
        foreach (self::$extensions as $extension) {
            $filtered_resources = $extension->filter_resources($filtered_resources, $user_style, $userid);
        }
        
        return $filtered_resources;
    }
}

// Ejemplo de extensión personalizada
class custom_analytics_extension implements learningstylesurvey_extension_interface {
    
    public function process_detected_style($style, $userid, $scores) {
        // Enviar datos a sistema de analíticas externo
        $this->send_to_analytics([
            'event' => 'learning_style_detected',
            'user_id' => $userid,
            'style' => $style,
            'scores' => $scores,
            'timestamp' => time()
        ]);
    }
    
    public function filter_resources($resources, $user_style, $userid) {
        // Aplicar filtrado basado en machine learning
        return $this->ml_filter_resources($resources, $user_style, $userid);
    }
    
    private function send_to_analytics($data) {
        // Implementación de envío a analytics
    }
    
    private function ml_filter_resources($resources, $user_style, $userid) {
        // Implementación de filtrado con ML
        return $resources;
    }
}

// Registrar extensión
learningstylesurvey_extension_manager::register_extension(
    'custom_analytics', 
    new custom_analytics_extension()
);
```

---

## 23. Glosario de Términos y Conceptos

### 23.1. Términos del Dominio Educativo

#### **Learning Style Survey (LSS)**
Sistema de encuesta para detectar estilos de aprendizaje de estudiantes basado en el modelo Felder-Silverman.

#### **Index of Learning Styles (ILS)**
Cuestionario de 44 preguntas desarrollado por Felder-Silverman para identificar preferencias de aprendizaje en 4 dimensiones.

#### **Estilo de Aprendizaje**
Preferencia individual de procesamiento de información que influye en cómo una persona aprende mejor.

#### **Dimensiones de Aprendizaje Felder-Silverman**:
- **Activo/Reflexivo**: ¿Cómo se procesa información?
- **Sensorial/Intuitivo**: ¿Qué tipo de información se prefiere?
- **Visual/Verbal**: ¿Cómo se recibe información de manera más efectiva?
- **Secuencial/Global**: ¿En qué orden se entiende información?

#### **Ruta de Aprendizaje Adaptativa**
Secuencia personalizada de recursos y evaluaciones basada en el estilo de aprendizaje detectado del estudiante.

#### **Navegación Adaptativa**
Sistema que modifica automáticamente el camino del estudiante basado en su rendimiento en evaluaciones.

#### **Tema de Refuerzo**
Contenido adicional presentado cuando un estudiante no alcanza el nivel requerido en una evaluación.

### 23.2. Términos Técnicos del Plugin

#### **Course Module (CM)**
Instancia específica del plugin en un curso de Moodle. Cada curso puede tener múltiples módulos.

#### **Course Module ID (CMID)**
Identificador único del módulo dentro del contexto de Moodle, usado para navegación y permisos.

#### **Path ID**
Identificador único de una ruta de aprendizaje específica.

#### **Step Number**
Número secuencial que define el orden de pasos en una ruta (1, 2, 3, etc.).

#### **Resource ID**
Identificador que puede apuntar a un recurso didáctico o a un quiz, dependiendo del campo `istest`.

#### **Pass/Fail Redirect**
Configuración que determina a qué tema dirigir al estudiante según apruebe o repruebe una evaluación.

#### **User Progress**
Registro del estado actual del estudiante en su ruta de aprendizaje.

### 23.3. Estados y Valores del Sistema

#### **Estados de Progreso de Usuario**:
- `'inprogress'`: Estudiante actualmente cursando la ruta
- `'completed'`: Ruta completada exitosamente
- `'blocked'`: Progreso bloqueado (generalmente por reprobar evaluaciones)

#### **Tipos de Paso**:
- `istest = 0`: Paso contiene recursos didácticos
- `istest = 1`: Paso contiene una evaluación/quiz

#### **Estilos de Aprendizaje Válidos**:
- `'activo'`: Aprende experimentando y haciendo
- `'reflexivo'`: Aprende observando y pensando
- `'sensorial'`: Prefiere hechos, datos, experimentación
- `'intuitivo'`: Prefiere teorías, significados, posibilidades
- `'visual'`: Aprende mejor con imágenes, diagramas, demostraciones
- `'verbal'`: Aprende mejor con palabras, discusiones
- `'secuencial'`: Aprende en pasos lógicos secuenciales
- `'global'`: Necesita ver el panorama completo primero

### 23.4. Conceptos de Base de Datos

#### **Tabla Principal vs Tabla de Navegación**
- **Tabla Principal**: `learningstylesurvey` (instancia del módulo)
- **Tabla de Navegación**: `learningpath_steps` (define secuencia de pasos)

#### **Relaciones Críticas**:
```
Usuario → Estilo (1:1) → Ruta (1:N) → Pasos (1:N) → Recursos/Quizzes
```

#### **Índices y Claves**:
- Siempre usar `ORDER BY id ASC` para opciones de quiz
- `temaid` vs `tema`: usar `tema` en `learningstylesurvey_resources`
- `userid` en recursos = profesor creador, NO estudiante

### 23.5. Conceptos de Seguridad

#### **Capability Checks**
Verificaciones de permisos usando el sistema de roles de Moodle.

#### **Context Validation**
Verificación de que el usuario tiene acceso al contexto específico (curso, módulo).

#### **CSRF Protection**
Protección contra ataques Cross-Site Request Forgery usando tokens de sesión.

#### **Path Traversal Prevention**
Prevención de acceso a archivos fuera del directorio permitido.

### 23.6. Conceptos de Performance

#### **Consultas N+1**
Problema de rendimiento donde se ejecutan múltiples consultas cuando una sola sería suficiente.

#### **Cache Strategy**
Estrategia de almacenamiento temporal de datos frecuentemente accedidos.

#### **Lazy Loading**
Carga de datos solo cuando se necesitan, no al inicio.

#### **Eager Loading**
Carga anticipada de datos relacionados en una sola consulta.

---

## 24. Referencias y Recursos Adicionales

### 24.1. Documentación Oficial

#### **Moodle Developer Documentation**
- [Moodle Developer Hub](https://moodledev.io/)
- [Plugin Development](https://moodledev.io/docs/apis/plugins)
- [Database API](https://moodledev.io/docs/apis/core/dml)
- [Form API](https://moodledev.io/docs/apis/core/form)
- [Output API](https://moodledev.io/docs/apis/core/output)

#### **Moodle Security**
- [Security Guidelines](https://moodledev.io/docs/security)
- [Coding Guidelines](https://moodledev.io/docs/guides/codingstyle)
- [Testing Guidelines](https://moodledev.io/docs/guides/testing)

### 24.2. Teoría de Estilos de Aprendizaje

#### **Felder-Silverman Learning Style Model**
- Felder, R. M., & Silverman, L. K. (1988). Learning and teaching styles in engineering education.
- Felder, R. M., & Spurlin, J. (2005). Applications, reliability and validity of the index of learning styles.

#### **Recursos sobre ILS**
- [NC State University - ILS Information](https://www.engr.ncsu.edu/stemconference/proceedings/2005/felder-spurlin/felder-spurlin.pdf)
- [Official ILS Questionnaire](https://www.webtools.ncsu.edu/learningstyles/)

### 24.3. Tecnologías Utilizadas

#### **PHP**
- [PHP Official Documentation](https://www.php.net/docs.php)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)

#### **MySQL/MariaDB**
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)

#### **JavaScript**
- [MDN Web Docs](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
- [jQuery Documentation](https://api.jquery.com/)

#### **HTML/CSS**
- [HTML5 Specification](https://html.spec.whatwg.org/)
- [CSS3 Reference](https://www.w3.org/Style/CSS/)

### 24.4. Herramientas de Desarrollo

#### **IDEs y Editores**
- **PhpStorm**: IDE especializado en PHP con soporte para Moodle
- **Visual Studio Code**: Editor ligero con extensiones para PHP y Moodle
- **Sublime Text**: Editor rápido con plugins para desarrollo web

#### **Debugging y Profiling**
- **Xdebug**: Debugger y profiler para PHP
- **PHP_CodeSniffer**: Herramienta para verificar estándares de código
- **PHPUnit**: Framework de testing unitario para PHP

#### **Control de Versiones**
- **Git**: Sistema de control de versiones distribuido
- **GitHub**: Plataforma de hosting para repositorios Git

### 24.5. Comunidad y Soporte

#### **Foros de Moodle**
- [Moodle.org Forums](https://moodle.org/mod/forum/)
- [Developer Forums](https://moodle.org/mod/forum/view.php?id=55)

#### **Stack Overflow**
- [Moodle Tags](https://stackoverflow.com/questions/tagged/moodle)
- [PHP Tags](https://stackoverflow.com/questions/tagged/php)

#### **Documentación del Proyecto**
- **Repositorio GitHub**: [https://github.com/EderPG/learningstylesurvey](https://github.com/EderPG/learningstylesurvey)
- **Issues y Bug Reports**: Usar el sistema de issues de GitHub
- **Wiki del Proyecto**: Documentación adicional en el wiki del repositorio

---

## 25. Créditos y Licencia

### 25.1. Desarrolladores

**Desarrollador Principal:**
- **EderPG** - Arquitectura, desarrollo inicial, implementación de algoritmos

**Colaboradores:**
- Comunidad de usuarios que han reportado bugs y sugerencias
- Testers que han validado funcionalidad en diferentes entornos
- Contribuidores de documentación y traducciones

### 25.2. Agradecimientos

**Agradecimientos especiales a:**
- **Richard M. Felder** y **Linda K. Silverman** por el desarrollo del modelo de estilos de aprendizaje
- **Comunidad Moodle** por el framework y las mejores prácticas
- **North Carolina State University** por la investigación en estilos de aprendizaje
- **Usuarios beta** que proporcionaron feedback durante el desarrollo

### 25.3. Licencia

Este plugin se distribuye bajo la **GNU General Public License v3.0**.

```
Learning Style Survey Plugin for Moodle
Copyright (C) 2025 EderPG

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
```

### 25.4. Términos de Uso

#### **Uso Educativo**
Este plugin está diseñado específicamente para uso educativo en instituciones académicas. Se permite y se fomenta su uso para:
- Investigación en estilos de aprendizaje
- Implementación de aprendizaje adaptativo
- Mejora de metodologías educativas

#### **Modificaciones y Redistribución**
Bajo los términos de GPL v3:
- ✅ Permitido modificar el código fuente
- ✅ Permitido redistribuir modificaciones
- ✅ Permitido uso comercial
- ❗ Requerido mantener la misma licencia GPL v3
- ❗ Requerido incluir código fuente de modificaciones

#### **Limitaciones de Responsabilidad**
- El software se proporciona "tal como es"
- No hay garantía de funcionamiento perfecto
- Los desarrolladores no son responsables por pérdida de datos
- Se recomienda hacer backups antes de instalación/actualización

### 25.5. Cómo Contribuir

#### **Reportar Bugs**
1. Verificar que el bug no esté ya reportado en GitHub Issues
2. Proporcionar información detallada del entorno (versión de Moodle, PHP, etc.)
3. Incluir pasos para reproducir el problema
4. Adjuntar logs relevantes si están disponibles

#### **Sugerir Mejoras**
1. Abrir un Issue en GitHub con etiqueta "enhancement"
2. Describir claramente la funcionalidad propuesta
3. Explicar el beneficio educativo de la mejora
4. Proporcionar mockups o ejemplos si es posible

#### **Contribuir Código**
1. Fork del repositorio en GitHub
2. Crear branch para la nueva funcionalidad
3. Seguir los estándares de código de Moodle
4. Escribir tests para nueva funcionalidad
5. Crear Pull Request con descripción detallada

#### **Contribuir Documentación**
1. Identificar áreas con documentación insuficiente
2. Seguir el formato establecido en esta documentación
3. Incluir ejemplos prácticos cuando sea posible
4. Verificar que la documentación sea técnicamente correcta

#### **Contribuir Traducciones**
1. Revisar archivos en `/lang/en/` para identificar strings
2. Crear directorio para nuevo idioma (ej: `/lang/es/`)
3. Traducir todos los strings manteniendo consistencia
4. Probar la traducción en entorno real

---

## 26. Historial de Versiones

### 26.1. Registro de Cambios (Changelog)

#### **Versión 1.0.0 (2025-01-XX) - Versión Inicial**
- ✨ Implementación inicial del sistema de encuestas ILS
- ✨ Sistema de detección de estilos de aprendizaje Felder-Silverman
- ✨ Rutas de aprendizaje adaptativas
- ✨ Sistema de recursos filtrados por estilo
- ✨ Evaluaciones con navegación condicional
- ✨ Sistema de refuerzo para estudiantes con dificultades
- 📚 Documentación completa del sistema

#### **Mejoras Implementadas**:
- 🔧 Sistema de navegación adaptativa basado en resultados de evaluaciones
- 🔧 Filtrado automático de recursos por estilo de aprendizaje
- 🔧 Interfaz diferenciada para estudiantes y profesores
- 🔧 Herramientas de diagnóstico para administradores
- 🔧 Sistema de progreso persistente por usuario
- 🔧 Gestión completa de temas educativos
- 🔧 CRUD completo para recursos y evaluaciones

#### **Correcciones de Bugs**:
- 🐛 Corregido problema de evaluación donde todas las respuestas se marcaban incorrectas
- 🐛 Corregido ordenamiento inconsistente de opciones en quizzes
- 🐛 Corregido filtrado de recursos por estilo case-sensitive
- 🐛 Corregido sistema de saltos adaptativos en evaluaciones
- 🐛 Corregido problema de múltiples intentos de examen
- 🐛 Corregida navegación entre pasos de ruta

#### **Optimizaciones**:
- ⚡ Optimizadas consultas de base de datos con JOINs eficientes
- ⚡ Implementado sistema de caché para estilos de usuario
- ⚡ Reducidas consultas N+1 en vista de estudiante
- ⚡ Optimizada carga de recursos filtrados

### 26.2. Roadmap de Desarrollo Futuro

#### **Versión 1.1.0 (Planeada)**
- 🔮 Dashboard mejorado para profesores con analíticas
- 🔮 Sistema de notificaciones automáticas
- 🔮 Exportación de reportes en PDF/Excel
- 🔮 API REST para integración con sistemas externos
- 🔮 Mejoras en la interfaz de usuario

#### **Versión 1.2.0 (Planeada)**
- 🔮 Soporte para múltiples algoritmos de detección de estilos
- 🔮 Sistema de recomendaciones basado en machine learning
- 🔮 Integración con calificador de Moodle
- 🔮 Soporte para contenido multimedia avanzado (H5P)
- 🔮 Analíticas predictivas de rendimiento estudiantil

#### **Versión 2.0.0 (Visión a largo plazo)**
- 🔮 Refactorización completa a arquitectura orientada a eventos
- 🔮 Soporte para microaprendizaje adaptativos
- 🔮 Integración con blockchain para certificación
- 🔮 IA avanzada para personalización automática de contenido
- 🔮 Soporte multi-tenant nativo

### 26.3. Compatibilidad

#### **Versiones de Moodle Soportadas**:
- ✅ Moodle 3.9 LTS (testado)
- ✅ Moodle 3.11 LTS (testado)
- ✅ Moodle 4.0 (testado)
- ✅ Moodle 4.1+ (compatible, testing en progreso)

#### **Versiones de PHP Soportadas**:
- ✅ PHP 7.2+ (mínimo)
- ✅ PHP 7.4 (recomendado)
- ✅ PHP 8.0+ (soportado)

#### **Bases de Datos Soportadas**:
- ✅ MySQL 5.7+
- ✅ MariaDB 10.2+
- ✅ PostgreSQL 10+
- ⚠️ SQLite (limitado, solo para development)

---

**📖 Fin de la Documentación Técnica Completa**

*Esta documentación cubre todos los aspectos técnicos, funcionales y de desarrollo del plugin Learning Style Survey para Moodle. Ha sido diseñada para proporcionar información exhaustiva para usuarios, administradores y desarrolladores.*

*Última actualización: Enero 2025*
*Versión de documentación: 1.0.0*

Desarrollado por **EderPG** y colaboradores.
Agradecimientos a la comunidad Moodle y usuarios que han aportado sugerencias y reportes.

---