<!DOCTYPE html>
<?php
//include QR-Code generator file
include('QR_BarCode.php');

//Object for QR Code
$qr = new QR_BarCode()
        
?>
<html lang="en">
    <head>
        <title>QR-Code Generating Example</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    </head>
    <body>
        <div class="container">
            <h2 class="text-danger">QR-Code Generating Example</h2>
            <form method="post">
                <div class="form-group">
                  <label for="Name">Name</label>
                  <input type="text" placeholder="Name" class="form-control" name="name" required="">
                </div>
                    <div class="form-group">
                  <label for="Email">Email address</label>
                  <input type="email" placeholder="Email" class="form-control" name="email" required="">
                </div>
              <div class="statusMsg"></div>
              <input type="submit" name="submit" class="btn btn-danger" value="Submit"/>
          </form>
            <div>
                <?php
                if(isset($_POST['submit'])){
                    $name = $_POST['name'];
                    $email = $_POST['email'];
                    
                    //create text QR code
                    $qr->info($name, $email);

                    //Save QR in image
                    //$qr->qrCode(400,'qr-legendblogs.png');
					$qr->qrCode(400);
                }
                //Display QR
                ?>
                <img src="https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=0xe2ac9631b1426ab753b08e0eea8a3b0b0e29e015|2000" alt="qr-legendblogs" />
            </div>
        </div>
    </body>
</html>
