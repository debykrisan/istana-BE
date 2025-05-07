<!DOCTYPE html>
<html>
    <body>
        <h1>Lupa Password</h1>
        <p>Hi, <?php 
                //import data from controller
                echo $data['email'];

            ?>
        </p>
        <p>Klik link di bawah ini untuk reset password anda</p>
        <button role="button"><a href="<?php echo $data['url']; ?>" target="_blank">Klik Disini</a></button>
    </body>
</html>

