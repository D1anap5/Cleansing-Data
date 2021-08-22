<?php
include 'connection.php';

$csvFile = 'langkah-3.csv';
$file_handle = fopen($csvFile, 'r');
while (!feof($file_handle)) {
    $rows[] = fgetcsv($file_handle, 130000); //jangan kurang dari panjang row di $csvFile
}
fclose($file_handle);

foreach ($rows as $row) {
    $objConnect = oci_connect($user, $pass, $db);
    $strSQL = "UPDATE collections SET ";
    $strSQL .= "noinduk_deposit = '" . $row[5] . "' ";
    $strSQL .= "WHERE id = '" . $row[1] . "' ";
    $objParse = oci_parse($objConnect, $strSQL);
    $objExecute = oci_execute($objParse, OCI_DEFAULT);
    if ($objExecute) {
        oci_commit($objConnect);
        echo "Mengisi <b>noindukdeposit = " . $row[5] . "</b> pada ID= " . $row[0] . " title = " . $row[2] . "<br/>";
    } else {
        oci_rollback($objConnect);
        $e = oci_error($objParse);
        echo "Error Save [" . $e['message'] . "]";
    }
    oci_close($objConnect);
}
?>