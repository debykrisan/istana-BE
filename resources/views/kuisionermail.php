<!DOCTYPE html>
<html>
    <body>
        <h1>Kuisioner</h1>
        <p>Halo, <?php 
                //import data from controller
                echo $data['nama'];

            ?>
        </p>
        <p>Terimakasih telah melakukan kunjungan ke <?php echo $data['istana']?></p>
        <p>Mohon mengisi kuisioner dengan klik link dibawah ini</p>
        <button role="button"><a href="<?php echo $data['url']; ?>" target="_blank">Klik Disini</a></button>
    </body>
</html>

