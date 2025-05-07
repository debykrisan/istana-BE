<?php
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename=daftar-obat.xlsx');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
ob_clean();
flush();

?>
<style>
    body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.45;
        font-family: Garamond, "Times New Roman", serif;
        color: #000;
        background: none;
        font-size: 14pt;
    }

    table {
        margin: 1px;
        text-align: left;
    }

    th {
        border-bottom: 1px solid #333;
        font-weight: bold;
    }

    td {
        border-bottom: 1px solid #333;
    }

    th, td {
        padding: 4px 10px 4px 0;
    }

    tfoot {
        font-style: italic;
    }

    caption {
        background: #fff;
        margin-bottom: 2em;
        text-align: left;
    }

    thead {
        display: table-header-group;
    }

    img, tr {
        page-break-inside: avoid;
    }

    /* Hide various parts from the site
    #header, #footer, #navigation, #rightSideBar, #leftSideBar
    {display:none;}
    */
</style>
<table  >
    <thead>
    <tr style="padding: 4px 10px 4px 0;" >

        <th >
            Obat
        </th>
        <th  >
            Volume
        </th>
        <th  >
            Satuan
        </th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!empty($data)) {
        foreach ($data as $dt) {
            ?>
            <tr style="padding: 4px 10px 4px 0;border: black solid 1px;">
                <td><?php echo $dt->nama; ?></td>
                <td><?php echo $dt->volume; ?></td>
                <td><?php echo $dt->satuan; ?></td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>
