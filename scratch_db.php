<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getConnection();
echo "--- TRAM CUU HO ---\n";
$rows = $db->query("SELECT * FROM tram_cuu_ho")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
echo "--- CHUNG TU GIAI NGAN ---\n";
$rowsCT = $db->query("SELECT * FROM chung_tu_giai_ngan")->fetchAll(PDO::FETCH_ASSOC);
print_r($rowsCT);
