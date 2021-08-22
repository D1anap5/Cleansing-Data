<?php
include 'connection.php';
if ($c = OCILogon($user, $pass, $db)) {
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
    die();

} else {
    $err = OCIError();
    echo "Connection failed." . $err[text];
}
OCILogoff($c);
?>