<?php
// Script de prueba para verificar el filtrado de opciones
$test_options = [
    '1',      // Número como string
    '2',      // Número como string
    '3',      // Número como string
    '4',      // Número como string
    '',       // Vacío
    '   ',    // Espacios
    'Opción A', // Texto normal
    '0'       // Cero como string
];

echo "<h2>Prueba de filtrado de opciones</h2>";
echo "<h3>Opciones originales:</h3>";
echo "<pre>";
print_r($test_options);
echo "</pre>";

echo "<h3>Después del filtrado (trim(opt) !== ''):</h3>";
$filtered = array_filter($test_options, fn($opt) => trim($opt) !== '');
echo "<pre>";
print_r($filtered);
echo "</pre>";

echo "<h3>Cantidad de opciones válidas: " . count($filtered) . "</h3>";

// Probar con diferentes tipos de valores
echo "<h3>Pruebas adicionales:</h3>";
$numbers_only = ['1', '2', '3', '4'];
$numbers_filtered = array_filter($numbers_only, fn($opt) => trim($opt) !== '');
echo "<p>Números solo: " . count($numbers_filtered) . " opciones válidas</p>";

$with_empty = ['1', '2', '', '4'];
$with_empty_filtered = array_filter($with_empty, fn($opt) => trim($opt) !== '');
echo "<p>Con vacío: " . count($with_empty_filtered) . " opciones válidas</p>";
?>
