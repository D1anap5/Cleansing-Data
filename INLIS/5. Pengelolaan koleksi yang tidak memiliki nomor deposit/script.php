<?php
include 'connection.php';
include 'logs.php';
if (!$c) {
    die("Tidak dapat melakukan koneksi dengan database Oracle");
}
$log = date('h:m:s') . " : Start cleansing collections dengan nomor deposit null";
writeLog($log);
echo $log;
/*-------------------------------------------------------------------------
------------1. List Koleksi yang no induk null
---------------------------------------------------------------------------*/

$query = "SELECT catalog_id, collections.id, title, noinduk_deposit, publishlocation
                FROM collections
                WHERE noinduk_deposit IS null AND category_id = 4";
$resultCsv = oci_parse($c, $query);
oci_execute($resultCsv);
$csv = [];
while ($row = oci_fetch_array($resultCsv)) {
    $csv[] = $row;
}
$file_name = "langkah-1.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["catalog_id", "collection_id", "title", "noinduk_deposit", "publishlocation"]);
foreach ($csv as $row) {
    fputcsv($output, $row);
}
fclose($output);
$log =  date('h:m:s') . " : Membuat list dengan noinduk_deposit null selesai";
writeLog($log);
echo $log;
/*------------------------------------------------------------------------------
------------2. List Koleksi yang no induk null join dengan catalog pada tag 090
--------------------------------------------------------------------------------*/

$query = "SELECT catalog_id, collections.id, title,noinduk_deposit, publishlocation, value
    FROM collections
    JOIN catalog_ruas ON collections.catalog_id = catalog_ruas.catalogid
    WHERE noinduk_deposit IS null
    AND category_id = 4
    AND tag='090'";
$resultCsv = oci_parse($c, $query);
oci_execute($resultCsv);
$csv = [];
while ($row = oci_fetch_array($resultCsv)) {
    $csv[] = [
        $row[0],
        $row[1],
        $row[2],
        $row[3],
        $row[4],
        $row[5],
    ];
}
$file_name = "langkah-2.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["catalog_id", "collection_id", "title", "noinduk_deposit", "publishlocation", "tag_090"]);
foreach ($csv as $row) {
    fputcsv($output, $row);
}
fclose($output);
$log = date('h:m:s') . " : Membuat list koleksi join dengan catalog pada tag 090 selesai";
writeLog($log);
echo $log;
/*------------------------------------------------------------------------------
------------3. Clean tag 090
--------------------------------------------------------------------------------*/
$csvFile = 'langkah-2.csv';
$file_handle = fopen($csvFile, 'r');
while (!feof($file_handle)) {
    $rows[] = fgetcsv($file_handle, 130000); //jangan kurang dari panjang row di $csvFile
}
fclose($file_handle);
$csv = [];
$csv2 = [];

foreach ($rows as $row) {
    $tag_090 = trim(str_replace('$a', '', $row[5]));
    $tag_090 = trim(str_replace('##', '', $tag_090));
    if (strtolower(substr($tag_090, 0, 2)) == "cb" ||
        strtolower(substr($tag_090, 0, 2)) == "cm" ||
        strtolower(substr($tag_090, 0, 2)) == "cl" ||
        strtolower(substr($tag_090, 0, 2)) == "ck" ||
        strtolower(substr($tag_090, 0, 2)) == "cs" ||
        strtolower(substr($tag_090, 0, 2)) == "rf" ||
        strtolower(substr($tag_090, 0, 2)) == "rs" ||
        strtolower(substr($tag_090, 0, 2)) == "rg" ||
        strtolower(substr($tag_090, 0, 2)) == "rm" ||
        strtolower(substr($tag_090, 0, 2)) == "rk") {

        $jilid = explode(';', $tag_090);
        if (count($jilid) == 1) {
            $csv[] = [
                $row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                $tag_090,
            ];
        } else {
            $csv2[] = [
                $row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                $tag_090,
            ];
        }
    }
}

$file_name = "langkah-3.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["catalog_id", "collection_id", "title", "noinduk_deposit", "publishlocation", "tag_090"]);
foreach ($csv as $row) {
    fputcsv($output, $row);
}
fclose($output);

$file_name2 = "langkah-3-jilid.csv";
$output2 = fopen(getcwd() . '/' . $file_name2, "w");
fputcsv($output2, ["catalog_id", "collection_id", "title", "noinduk_deposit", "publishlocation", "tag_090"]);
foreach ($csv2 as $row) {
    fputcsv($output2, $row);
}
fclose($output2);
$log = date('h:m:s') . " : Membersihkan tag 090 selesai";
writeLog($log);
echo $log;
/*------------------------------------------------------------------------------
------------4. Eksekusi ke database untuk koleksi non jilid
--------------------------------------------------------------------------------*/
$csvFile = 'langkah-3.csv';
$file_handle = fopen($csvFile, 'r');
while (!feof($file_handle)) {
    $rows[] = fgetcsv($file_handle, 130000); //jangan kurang dari panjang row di $csvFile
}
fclose($file_handle);

foreach ($rows as $row) {
    $strSQL = "UPDATE collections SET ";
    $strSQL .= "noinduk_deposit = '" . $row[5] . "' ";
    $strSQL .= "WHERE id = '" . $row[1] . "' ";
    $objParse = oci_parse($c, $strSQL);
    $objExecute = oci_execute($objParse, OCI_DEFAULT);
    if ($objExecute) {
        oci_commit($c);
        writeLog(date('h:m:s') . " : Mengisi noindukdeposit = " . $row[5] . " pada ID= " . $row[0] . " title = " . $row[2]);
        echo "Mengisi <b>noindukdeposit = " . $row[5] . "</b> pada ID= " . $row[0] . " title = " . $row[2] . "<br/>";
    } else {
        oci_rollback($c);
        $e = oci_error($objParse);
        echo "Error Save [" . $e['message'] . "]";
    }
}

$log = date('h:m:s') . " : Eksekusi collections noinduk_deposit null untuk koleksi non jilid selesai";
writeLog($log);
echo $log;
?>