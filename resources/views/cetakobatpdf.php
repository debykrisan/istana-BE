<html lang="en">
<head>
<style>
    body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.45;

        background: none;
        font-size: 14pt;
    }

    table {
        margin: 1px;
        width: 100%;
        border-left: 0.1em solid black;
        border-right: 0.1em solid black;
        border-top: 0.1em solid black;
        border-bottom: 0.1em solid black;
        border-collapse: collapse;
        padding: 4px 10px 4px 0;
    }
    tr{

    }

    th {

        font-weight: bold;
        text-align: center;
    }

    td , th{
        border-left: 0.1em solid black;
        border-right: 0.1em solid black;
        border-top: 0.1em solid black;
        border-bottom: 0.1em solid black;
        padding: 4px 10px 4px 4px;

    }



    tfoot {
        font-style: italic;
    }

    caption {
        background: #fff;
        margin-bottom: 2em;
        text-align: center;
        font-style: oblique;
        font-size: large;
        font-weight: bold;

    }

    thead {
        display: table-header-group;
    }

    img, tr {
        page-break-inside: avoid;
    }
    @page{
        margin-top: 100px; /* create space for header */
        margin-bottom: 70px; /* create space for footer */
    }
    header, footer{
        position: fixed;
        left: 0px;
        right: 0px;
    }
    header{
        height: 60px;
        margin-top: -60px;
        margin-bottom: 10px;
        text-align: right;
        font-size: small;
        font-style: italic;
    }
    footer{
        height: 50px;
        margin-bottom: -50px;
        font-size: small;
    }

</style>
</head>
<body>
<header>
    <h7>Digivet MR - <?php echo date("d-m-Y");?> </h7>
    <hr/>
    <p>&nbsp;</p>
</header>

<main>
<table    >
    <caption>Laporan Data Obat Satwa</caption>
    <thead>
    <tr  >

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
            <tr  style="padding: 4px 10px 4px 0;border: solid black 1px;"  >
                <td><?php echo $dt->nama; ?></td>
                <td style="text-align: right;"><?php echo $dt->volume; ?></td>
                <td style="text-align: right;"><?php echo $dt->satuan; ?></td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>
</main>
<footer>
    <p>Copyright Setneg &copy; <?php echo date("Y");?></p>
</footer>
</body>
</html>
