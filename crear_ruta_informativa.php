
<?php
require_once('../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);

echo $OUTPUT->header();
echo "<div class='container'>";
echo "<h2>Ruta Informativa - Subir Recurso</h2>";
?>

<form action="uploadresource.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">

    <div>
        <label>Nombre del recurso:</label><br>
        <input type="text" name="resourcename" required>
    </div>

    <div style="margin-top: 10px;">
        <label>Archivo:</label><br>
        <input type="file" name="resourcefile" required>
    </div>

    <div style="margin-top: 10px;">
        <label>Indicaciones:</label><br>
        <textarea name="instructions" rows="4" cols="50" required></textarea>
    </div>

    <div style="margin-top: 10px;">
        <label>Orden del paso (steporder):</label><br>
        <input type="number" name="steporder" min="0" required>
    </div>

    <div style="margin-top: 10px;">
        <label>Estilo de aprendizaje:</label><br>
        
<select name="style" required>
    <option value="sensitivo">Sensitivo</option>
    <option value="intuitivo">Intuitivo</option>
    <option value="visual">Visual</option>
    <option value="verbal">Verbal</option>
    <option value="activo">Activo</option>
    <option value="reflexivo">Reflexivo</option>
    <option value="secuencial">Secuencial</option>
    <option value="global">Global</option>
</select>

    </div>

    <div style="margin-top: 20px;">
        <button type="submit">Guardar recurso</button>
    </div>
</form>

<?php
echo "</div>";
echo $OUTPUT->footer();
?>
