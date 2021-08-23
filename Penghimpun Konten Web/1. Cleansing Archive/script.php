<?php
include 'connection.php';
include 'logs.php';
writeLog(date('h:m:s') . " : Start cleansing archive");
/*-----------------------------------------------------------------
---1. Membuat list archive dengan URL yang dapat di akses----------
------dan tidak dapat diakses--------------------------------------
-----------------------------------------------------------------*/
$sql = "SELECT archive_id as id, title, url FROM archives where archive_id < 5";
$result = $conn->query($sql);
$file_name = "langkah-1.csv";

if ($result->num_rows > 0) {
    $output = fopen(getcwd() . '/' . $file_name, "w");
    fputcsv($output, ["id", "title", "url", "status"]);
    writeLog(date('h:m:s') . " : Ditemukan " . $result->num_rows . " archive");
    while ($row = $result->fetch_assoc()) {
        $url = $row["url"];
        writeLog(date('h:m:s') . " : Checking " . $row['url']);
        $headers = @get_headers($url);

        // Use condition to check the existence of URL
        if ($headers[0] == 'HTTP/1.1 404 Not Found') {
            $status = "URL Doesn't Exist";
        } else {
            $status = "URL Exist";
        }
        writeLog(date('h:m:s') . " : " . $row['url'] . " --> " . $status);
        $csv1[] = [$row["id"], $row["title"], $row["url"], $status];
        fputcsv($output, [$row["id"], $row["title"], $row["url"], $status]);
    }
    fclose($output);
} else {
    echo "0 results";
    die();
}

/*------------------------------------------------------
----------2. Hapus Records dan Records DC---------------
--------------------------------------------------------*/
$file_name = "langkah-2.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "record_id", "status records", "status records_dc"]);
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $sql = "SELECT records.id AS id, records.archive_id, record_dc.id = record_dc_id
                FROM records
                LEFT JOIN record_dc  on records.id = record_dc
                WHERE archive_id = $data[1]";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            writeLog(date('h:m:s') . " : Menghapus records ...");
            $row = $result->fetch_assoc();
            // output data of each row
            while ($row) {
                $sql = "DELETE FROM records_dc WHERE record_id = " . $row['id'];
                if ($conn->query($sql) === true) {
                    $success_record_dc = "Records_dc deleted successfully";
                } else {
                    $success_record_dc = "Error deleting record_dc: " . $conn->error;
                }

                $sql = "DELETE FROM records WHERE id = " . $row['id'];
                if ($conn->query($sql) === true) {
                    $success_record = "Record deleted successfully";
                } else {
                    $success_record = "Error deleting record: " . $conn->error;
                }
                fputcsv($output, [
                    $row["archive_id"],
                    $row["id"],
                    $success_record,
                    $success_record_dc,
                ]);
            }
        }
        
    }
}
fclose($output);
writeLog(date('h:m:s') . " : Dihapus " . count($csv2) . " records");

/*--------------------------------------------------------------------------------
----------3. Hapus document dan metadata file pada tabel files -------------------
----------------------------------------------------------------------------------*/
$set_file_directory = ""; //isi lokasi file root
$file_name = "langkah-3.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "file_id", "status_content", "status_data"]);
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $sql = "SELECT * FROM files where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // output data of each row
            while ($row) {
                //hapus document
                $file_pointer = $set_file_directory . $row['location'];
                if (!unlink($file_pointer)) {
                    $status_file = "$file_pointer cannot be deleted due to an error";
                } else {
                    $status_file = "$file_pointer has been deleted";
                }
                //hapus metadata file
                $sql = "DELETE FROM records WHERE id = " . $row['id'];
                if ($conn->query($sql) === true) {
                    $status_file_data = "Record files deleted successfully";
                } else {
                    $status_file_data = "Error deleting record files: " . $conn->error;
                }
                fputcsv($output,[
                    $row["archive_id"],
                    $row["id"],
                    $row["location"],
                    $status_file,
                    $status_file_data,
                ]);
            }
        }
    }
}
fclose($output);
writeLog(date('h:m:s') . " : Dihapus " . count($csv3) . " document dan metadata file");

/*--------------------------------------------------------------------------------------
---------4. Hapus archive_settings dengan archive_id yang tidak dapat diakses-----------
---------------------------------------------------------------------------------------*/
$file_name = "langkah-4.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "archive_setting_id", "setting_name", "status"]);
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $sql = "SELECT * FROM archive_settings where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // output data of each row
            while ($row) {
                $sql = "DELETE FROM archive_settings WHERE id = " . $row['id'];
                $success = "";
                if ($conn->query($sql) === true) {
                    $status = "Deleted successfully";
                } else {
                    $status = "Error deleting: " . $conn->error;
                }
                fputcsv($output, [
                    $row["archive_id"],
                    $row["id"],
                    $row["setting_name"],
                    $status,
                ]);
            }
        }
    }
}
fclose($output);
writeLog(date('h:m:s') . " : Dihapus " . count($csv4) . " archive_settings");

/*--------------------------------------------------------------------------------------
---------5. Hapus archive_sessions dengan archive_id yang tidak dapat diakses-----------
---------------------------------------------------------------------------------------*/
$file_name = "langkah-5.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "archive_session_id", "user_id", "status"]);
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $sql = "SELECT * FROM archive_sessions where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // output data of each row
            while ($row) {
                $sql = "DELETE FROM archive_sessions WHERE id = " . $row['id'];
                $success = "";
                if ($conn->query($sql) === true) {
                    $status = "Deleted successfully";
                } else {
                    $status = "Error deleting: " . $conn->error;
                }
 
                fputcsv($output, [
                    $row["archive_id"],
                    $row["id"],
                    $row["user_id"],
                    $status,
                ]);
            }
        }
    }
}
fclose($output);
writeLog(date('h:m:s') . " : Dihapus " . count($csv5) . " archive_sessions");

/*--------------------------------------------------------------------------------------
---------6. Hapus archive_subjects dengan archive_id yang tidak dapat diakses------------
---------------------------------------------------------------------------------------*/
$file_name = "langkah-6.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "subject_id"]);
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $sql = "SELECT * FROM archive_subjects where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
        }
        $sql = "DELETE FROM archive_subjects WHERE archive_id = " . $data['id'];
        $conn->query($sql);
    }
}
fclose($output);
writeLog(date('h:m:s') . " : Dihapus " . count($csv6) . " archive_subjects");

/*--------------------------------------------------------------------------------------
---------7. Hapus archives dengan archive_id yang tidak dapat diakses------------------
---------------------------------------------------------------------------------------*/
$file_name = "langkah-7.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "url", "title", "status"]);
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $sql = "DELETE FROM archives WHERE id = " . $data[1];
        $success = "";
        if ($conn->query($sql) === true) {
            $status = "Deleted successfully";
        } else {
            $status = "Error deleting: " . $conn->error;
        }
        fputcsv($output, [
            $data[0],
            $data[1],
            $data[2],
            $status,
        ]);
    }
}
fclose($output);
writeLog(date('h:m:s') . " : Dihapus " . count($csv7) . " archives");

/*--------------------------------------------------------------------------------------
---------8. Hasil akhir archive setelah di cleansing------------------------------------
---------------------------------------------------------------------------------------*/
$file_name = "langkah-8-hasil-akhir.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "title", "url"]);
$sql = "SELECT * FROM archives";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    fputcsv($output, [
        $row["archive_id"],
        $row["title"],
        $row["url"],
    ]);
}
fclose($output);
writeLog(date('h:m:s') . " : Selesai cleansing archive");
