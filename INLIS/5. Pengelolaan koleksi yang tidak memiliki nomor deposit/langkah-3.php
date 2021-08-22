<?php
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
        if(count($jilid) == 1) {
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
die();
?>