<?php
//deklarasikan connection database
$servername = "localhost";
$username = "root";
$password = "iamcak3p";

/*-----------------------------------------------------------------
---1. Membuat list archive dengan URL yang dapat di akses----------
------dan tidak dapat diakses--------------------------------------
-----------------------------------------------------------------*/
$conn = new mysqli($servername, $username, $password);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT id, title, url FROM archives";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $conn->close();
} else {
    echo "0 results";
    $conn->close();
    die();
}

while ($row) {
    $url = $row["url"];
    $headers = @get_headers($url);

    // Use condition to check the existence of URL
    if ($headers && strpos($headers[0], '200')) {
        $status = "URL Exist";
    } else {
        $status = "URL Doesn't Exist";
    }
    $csv1[] = [$row["id"], $row["title"], $row["url"], $status];
}
$file_name = "langkah-1.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["id", "title", "url", "status"]);
foreach ($csv1 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*------------------------------------------------------
----------2. Hapus Records dan Records DC---------------
--------------------------------------------------------*/
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $conn = new mysqli($servername, $username, $password);
        $sql = "SELECT records.id AS id, records.archive_id, record_dc.id = record_dc_id
                FROM records
                LEFT JOIN record_dc  on records.id = record_dc
                WHERE archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $conn->close();
            // output data of each row
            while ($row) {
                $conn = new mysqli($servername, $username, $password);
                $sql = "DELETE FROM records_dc WHERE record_id = " . $row['id'];
                if ($conn->query($sql) === true) {
                    $success_record_dc = "Records_dc deleted successfully";
                } else {
                    $success_record_dc = "Error deleting record_dc: " . $conn->error;
                }
                $conn->close();

                $conn = new mysqli($servername, $username, $password);
                $sql = "DELETE FROM records WHERE id = " . $row['id'];
                if ($conn->query($sql) === true) {
                    $success_record = "Record deleted successfully";
                } else {
                    $success_record = "Error deleting record: " . $conn->error;
                }
                $conn->close();

                $csv2[] = [
                    $row["archive_id"],
                    $row["id"],
                    $success_record,
                    $success_record_dc,
                ];
            }
        } else {
            $conn->close();
        }
    }
}
//export data record yang dihapus
$file_name = "langkah-2.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "record_id", "status records", "status records_dc"]);
foreach ($csv2 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*--------------------------------------------------------------------------------
----------3. Hapus document dan metadata file pada tabel files -------------------
----------------------------------------------------------------------------------*/
$set_file_directory = ""; //isi lokasi file root
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $conn = new mysqli($servername, $username, $password);
        $sql = "SELECT * FROM files where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $conn->close();
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
                $conn = new mysqli($servername, $username, $password);
                $sql = "DELETE FROM records WHERE id = " . $row['id'];
                if ($conn->query($sql) === true) {
                    $status_file_data = "Record files deleted successfully";
                } else {
                    $status_file_data = "Error deleting record files: " . $conn->error;
                }
                $conn->close();
                $csv3[] = [
                    $row["archive_id"],
                    $row["id"],
                    $row["location"],
                    $status_file,
                    $status_file_data,
                ];
            }
        } else {
            $conn->close();
        }
    }
}
//export data yang dihapus
$file_name = "langkah-3.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "file_id", "status_content", "status_data"]);
foreach ($csv3 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*--------------------------------------------------------------------------------------
---------4. Hapus archive_settings dengan archive_id yang tidak dapat diakses-----------
---------------------------------------------------------------------------------------*/
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $conn = new mysqli($servername, $username, $password);
        $sql = "SELECT * FROM archive_settings where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $conn->close();
            // output data of each row
            while ($row) {
                $conn = new mysqli($servername, $username, $password);
                $sql = "DELETE FROM archive_settings WHERE id = " . $row['id'];
                $success = "";
                if ($conn->query($sql) === true) {
                    $status = "Deleted successfully";
                } else {
                    $status = "Error deleting: " . $conn->error;
                }
                $conn->close();
                $csv4[] = [
                    $row["archive_id"],
                    $row["id"],
                    $row["setting_name"],
                    $status,
                ];
            }
        } else {
            $conn->close();
        }
    }
}
//export data yang dihapus
$file_name = "langkah-4.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "archive_setting_id", "setting_name", "status"]);
foreach ($csv4 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*--------------------------------------------------------------------------------------
---------5. Hapus archive_sessions dengan archive_id yang tidak dapat diakses-----------
---------------------------------------------------------------------------------------*/
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $conn = new mysqli($servername, $username, $password);
        $sql = "SELECT * FROM archive_sessions where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $conn->close();
            // output data of each row
            while ($row) {
                $conn = new mysqli($servername, $username, $password);
                $sql = "DELETE FROM archive_sessions WHERE id = " . $row['id'];
                $success = "";
                if ($conn->query($sql) === true) {
                    $status = "Deleted successfully";
                } else {
                    $status = "Error deleting: " . $conn->error;
                }
                $conn->close();
                $csv5[] = [
                    $row["archive_id"],
                    $row["id"],
                    $row["user_id"],
                    $status,
                ];
            }
        } else {
            $conn->close();
        }
    }
}
//export data yang dihapus
$file_name = "langkah-5.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "archive_session_id", "user_id", "status"]);
foreach ($csv5 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*--------------------------------------------------------------------------------------
---------6. Hapus archive_subjects dengan archive_id yang tidak dapat diakses------------
---------------------------------------------------------------------------------------*/
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $conn = new mysqli($servername, $username, $password);
        $sql = "SELECT * FROM archive_subjects where archive_id = $data[1]";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $csv6 = $result->fetch_assoc();
            $conn->close();
        }
        $conn = new mysqli($servername, $username, $password);
        $sql = "DELETE FROM archive_subjects WHERE archive_id = " . $data['id'];
        $conn->query($sql);
        $conn->close();
    }
}
//export data yang dihapus
$file_name = "langkah-6.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "subject_id"]);
foreach ($csv6 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*--------------------------------------------------------------------------------------
---------7. Hapus archives dengan archive_id yang tidak dapat diakses-----------
---------------------------------------------------------------------------------------*/
foreach ($csv1 as $data) {
    if ($data[3] == "URL Doesn't Exist") {
        $conn = new mysqli($servername, $username, $password);
        $sql = "DELETE FROM archives WHERE id = " . $data[1];
        $success = "";
        if ($conn->query($sql) === true) {
            $status = "Deleted successfully";
        } else {
            $status = "Error deleting: " . $conn->error;
        }
        $conn->close();
        $csv7[] = [
            $data[0],
            $data[1],
            $data[2],
            $status,
        ];
    }
}
//export data yang dihapus
$file_name = "langkah-7.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "url", "title", "status"]);
foreach ($csv7 as $data) {
    fputcsv($output, $data);
}
fclose($output);

/*--------------------------------------------------------------------------------------
---------8. Hasil akhir archive setelah di cleansing------------------------------------
---------------------------------------------------------------------------------------*/
$conn = new mysqli($servername, $username, $password);
$sql = "SELECT * FROM archives";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
}
$conn->close();
while ($row) {
    $csv8[] = [
        $row["archive_id"],
        $row["title"],
        $row["url"],
    ];
}
//export data
$file_name = "langkah-8-hasil-akhir.csv";
$output = fopen(getcwd() . '/' . $file_name, "w");
fputcsv($output, ["archive_id", "title", "url"]);
foreach ($csv8 as $data) {
    fputcsv($output, $data);
}
fclose($output);
