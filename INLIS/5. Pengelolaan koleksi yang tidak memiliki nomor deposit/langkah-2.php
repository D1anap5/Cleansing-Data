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
    $query = "SELECT catalog_id, title,noinduk_deposit, publishlocation, value
    FROM collections 
    JOIN catalog_ruas ON collections.catalog_id = catalog_ruas.catalogid
    WHERE noinduk_deposit IS null  
    AND category_id = 4
    AND tag='090'";
    $page = "SELECT * FROM ( select rownum as rnum, a.*
          FROM ( $query ) a )
          WHERE rownum <= $maxRow
          AND rnum >= $minRow";

    $result = oci_parse($c, $page);
    oci_execute($result);

    if (isset($_GET['download'])) {
        $csv = getData($c, $query);
        outputCSV($csv, "data_export_" . date("Y-m-d_h_m_s") . ".csv");
        die();
    }
    if(isset($_GET['execute'])) {
        $data = getData($c, $query);
        execute();
    }
    showTable($result, $prev, $next, $maxRow);
    OCILogoff($c);

} else {
    $err = OCIError();
    echo "Connection failed." . $err[text];
}

function showTable($result, $prev, $next, $maxRow)
{
    echo "<style>
        table, th, td {
            border : 1px solid #ccc;
            padding : 2 px;
        }
    </style>";
    echo "<h1>Collection yang No Induk Deposit Join dengan Catalogs </h1><table>";
    echo "<thead><th>No</th><th>Catalog ID</th><th>Title</th><th>No Induk Deposit</th><th>Lokasi Terbit</th><th>Tag 090</th></thead>";

    while ($row = oci_fetch_array($result)) {
        echo "<tr>
    <td>" . $row[0] . "</td>
    <td>" . $row[1] . "</td>
    <td>" . wordwrap($row[2], 100, "<br />\n") . "</td>
    <td> null</td>
    <td>" . $row[4] . "</td>
    <td>" . trim(str_replace('$a', '', $row[5])) . "</td>
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
            <a href='?execute=true'><button>Execute</button></a>";
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
    fputcsv($output, ["title", "catalogid", "no induk deposit", "lokasi terbit", "tag 90"]);
    foreach ($data as $row) {
        # Add the rows to the body
        fputcsv($output, $row); // here you can change delimiter/enclosure
    }
    # Close the stream off
    fclose($output);
}
function getData($c, $query)
{
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
            trim(str_replace('$a', '', $row[5]))
        ];
    }
    return $csv;
}
