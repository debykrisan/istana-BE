<?php
    if (!empty($data)) {
        foreach ($data as $dt) {
            ?>
            <h2>Halo, <?php echo $dt->c_nama_lengkap ?></h2>
            <p>Berikut adalah kode verifikasi anda : <b><?php echo $dt->c_ver_code ?></b></p>
            <?php
        }
    }
?>