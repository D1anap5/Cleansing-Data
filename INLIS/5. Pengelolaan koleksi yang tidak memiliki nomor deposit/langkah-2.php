<?php
include 'connection.php';
if ($c = OCILogon($user, $pass, $db)) {
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
            $row[5]
        ];
    }
    $file_name = "langkah-2.csv";
    $output = fopen(getcwd() . '/' . $file_name, "w");
    fputcsv($output, ["catalog_id", "collection_id", "title", "noinduk_deposit", "publishlocation", "tag_090"]);
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
