<?php
include 'connection.php';
include 'logs.php';
if (!$c) {
    die("Tidak dapat melakukan koneksi dengan database Oracle");
}
$log = date('h:m:s') . " : Start cleansing collections dengan nomor deposit null";
writeLog($log);
echo $log;

/* ----------------------------------------------------------------------------------------------------
1. membuat list master_group_publisher yang duplikat
----------------------------------------------------------------------------------------------------*/
$query = "SELECT publisher_group_name, count(publisher_group_name) as jumlah
            FROM master_publisher_group
            ORDER BY jumlah desc";
$result = oci_parse($c, $query);
oci_execute($result);
while ($row = oci_fetch_array($result)) {
    $csv[] = [$row[0], $row[1], $row[3]];
}

//export duplikat
$file_name = "langkah-1.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["publisher_group_name", "jumlah"]);
foreach ($csv as $row) {
    fputcsv($output, $row);
}
fclose($output);

/* ----------------------------------------------------------------------------------------------------
2. membuat list master_publisher yang akan dipindahkan ke publisher_group_id yang baru
----------------------------------------------------------------------------------------------------*/
foreach ($csv as $data) {
    if ($data[1] > 1) {
        //get publisher_group_id yang akan jadi master
        $query = "SELECT publisher_group_id
                    FROM master_publisher_group
                    WHERE publisher_group_name = '$data[0]'";
        $result = oci_parse($c, $query);
        oci_execute($result);
        $nrows = oci_fetch_all($result, $res);
        $publisher_group_id = $res[0]['publisher_group_id'];

        
        for ($i = 0; $i < count($res); $i++) {
            if ($i > 0) {
                /*--------2.1 get publishers yang akan dipindahkan publisher_group_id nya ------------*/
                $query = "SELECT publisher_id, publisher_name, publisher_group_id
                        FROM master_publisher
                        WHERE publisher_group_id = $publisher_group_id";
                $resultRes = oci_parse($c, $query);
                oci_execute($resultRes);
                $nrows = oci_fetch_all($resultRes, $res[$i]);
                while ($nrows) {
                    $csv2_1[] = [$nrows[0], $nrows[1], $data[0], $nrows[2], $publisher_group_id];
                }
                /*----------- 2.1 Hapus master group publisher -------------------------------------*/
                $query = "DELETE
                        FROM master_publisher_group
                        WHERE publisher_group_id = '$res[$i][0]'";
                $resultRes2 = oci_parse($c, $query);
                oci_execute($resultRes2);
                oci_commit($c);
                $csv2_2[] = [$res[$i][0], $data[0]];
            }
        }
    }
    //export publisher yang akan dipindahkan publisher_group_id nya
    $file_name = "langkah-2-1.csv";
    $output = fopen(getcwd() . '/' . $file_name, "w");
    fputcsv($output, ["publisher_id", "publisher_name", "publisher_group_name", "publisher_group_id_old", "publisher_group_id_new"]);
    foreach ($csv2_1 as $row) {
        fputcsv($output, $row);
    }
    fclose($output);

    // export group publisher yang duplikast dan dihapus
    $file_name = "langkah-2-2.csv";
    $output = fopen(getcwd() . '/' . $file_name, "w");
    fputcsv($output, ["publisher_group_id", "publisher_group_name"]);
    foreach ($csv2_2 as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

/* ----------------------------------------------------------------------------------------------------
3. Mengganti publisher_group_id yang lama dengan yang baru
----------------------------------------------------------------------------------------------------*/
if (count($csv2_1) > 0) {
    foreach ($csv2_1 as $data) {
        $strSQL = "UPDATE master_publishers SET ";
        $strSQL .= "publisher_group_id = '" . $data[4] . "' ";
        $strSQL .= "WHERE id = '" . $data[0] . "' ";
        $objParse = oci_parse($c, $strSQL);
        $objExecute = oci_execute($objParse, OCI_DEFAULT);
        if ($objExecute) {
            oci_commit($c);
            writeLog(date('h:m:s') . " : Mengganti publisher_group_id = " . $data[4] . " nama publisher = " . $data[1] . " nama group = " . $data[2]);
            echo date('h:m:s') . "Mengganti publisher_group_id = " . $data[4] . " nama publisher = " . $data[1] . " nama group = " . $data[2];
        } else {
            oci_rollback($c);
            $e = oci_error($objParse);
            echo "Error Save [" . $e['message'] . "]";
            writeLog(date('h:m:s') . " : Error Save [" . $e['message'] . "]");
        }
    }
}


/* ----------------------------------------------------------------------------------------------------
4. Deteksi publisher_group_id dengan nama yang mirip pada tabel master_publisher yang publisher_group_id nya masih null
----------------------------------------------------------------------------------------------------*/
/*$query = "SELECT publisher_group_id, publisher_group_name
            JOIN
            FROM master_publisher_group";
$result = oci_parse($c, $query);
oci_execute($result);
while ($row = oci_fetch_array($result)) {
    $csv4[] = [$row[0], $row[1], $row[3]];
}

//export duplikat
$file_name = "langkah-4.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["publisher_group_name", "jumlah"]);
foreach ($csv as $row) {
    fputcsv($output, $row);
}
fclose($output);*/
/* ----------------------------------------------------------------------------------------------------
5. Mengisi publisher_group_id ke dalam tabel master_publisher
----------------------------------------------------------------------------------------------------*/


OCILogoff($c);
