<?php
include('connection.php');
$page = 1;
$maxRow = 20;
$minRow = 1;
if (isset($_GET['row'])) {
    $maxRow = intval($_GET['row']);
}
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
    $minRow = (($page - 1) * $maxRow) + 1;
}
$next = $page + 1;
$prev = $page - 1;
if ($c = OCILogon($user, $pass, $db)) {
    $select = "catalog_id, collections.id, title, noinduk_deposit, publishlocation";
    $form = " FROM collections ";
    $where = " WHERE noinduk_deposit IS null AND category_id = 4";
    $query = "SELECT " . $select . $form . $where;

    $count = "SELECT count(*) AS TOTAL " . $form . $where;
    $stmt = oci_parse($c, $count);

    oci_execute($stmt);
    oci_fetch_all($stmt, $total);
    oci_num_rows($stmt);

    $page = "SELECT * FROM ( select rownum as rnum, a.*
          FROM ( $query ) a )
          WHERE rownum <= $maxRow
          AND rnum >= $minRow";

    $result = oci_parse($c, $page);
    oci_execute($result);

    if (isset($_GET['download'])) {
        download($c, $query);
        die();
    }
    showTable($result, $prev, $next, $maxRow, $total);
} else {
    $err = OCIError();
    echo "Connection failed." . $err[text];
}
OCILogoff($c);

function showTable($result, $prev, $next, $maxRow, $total)
{
    echo "<style>
        table, th, td {
            border : 1px solid #ccc;
            padding : 2 px;
        }
    </style>";
    echo "<h1>Collection yang No Induk Deposit = null </h1>
    <h2>Total Data : " . $total['TOTAL'][0] . "</h2>
    <table>";
    echo "<thead><th>No</th><th>Catalog ID</th><th>Collection ID</th><th>Title</th><th>No Induk Deposit</th><th>Lokasi Terbit</th></thead>";

    while ($row = oci_fetch_array($result)) {
        echo "<tr>
    <td>" . $row[0] . "</td>
    <td>" . $row[1] . "</td>
    <td>" . $row[2] . "</td>
    <td>" . wordwrap($row[3], 100, "<br />\n") . "</td>
    <td> null</td>
    <td>" . $row[5] . "</td>
    </tr>";
    }
    echo "</table><br/>";
    if ($prev > 0) {
        echo " <a href='?page=" . $prev . "&row=" . $maxRow . "'><button>PREV</button></a>";
    }
    echo "<a href='?page=" . $next . "&row=" . $maxRow . "'><button>NEXT</button></a>
            <br/>
            <br/>
            <a href='?download=true'><button>Download</button></a>
            <br/>
            <a href='langkah-2.php'><button>Langkah 2</button></a>";
}
function outputCSV($data, $file_name = 'file.csv')
{
    # output headers so that the file is downloaded rather than displayed
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$file_name");
    # Disable caching - HTTP 1.1
    header("Cache-Control: no-cache, no-store, must-revalidate");
    # Disable caching - HTTP 1.0
    header("Pragma: no-cache");
    # Disable caching - Proxies
    header("Expires: 0");

    # Start the ouput
    $output = fopen("php://output", "w");

    # Then loop through the rows
    foreach ($data as $row) {
        # Add the rows to the body
        fputcsv($output, $row); // here you can change delimiter/enclosure
    }
    # Close the stream off
    fclose($output);
}
function download($c, $query)
{
    $resultCsv = oci_parse($c, $query);
    oci_execute($resultCsv);
    $csv = [];
    while ($row = oci_fetch_array($resultCsv)) {
        $csv[] = [$row[0], $row[1], $row[2]];
    }
    outputCSV($csv, "data_export_" . date("Y-m-d_h_m_s") . ".csv");
}