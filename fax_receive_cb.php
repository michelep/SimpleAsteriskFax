#!/usr/bin/php

<?php

#
# SimpleAsteriskFax v0.0.1
#
# Michele <o-zone@zerozone.it> Pinassi
#
# A simple script to convert received FAXes to PDF and sent it to specified e-mail as attachment
# https://github.com/michelep/SimpleAsteriskFax
#
# To be used with PHPMailer (https://github.com/PHPMailer/PHPMailer)
#
# Released under GPL 3.0 License

if (PHP_SAPI != "cli") {
    exit;
}

if($argc < 7) {
    echo "Usage: $argv[0] [file tiff] [name] [remote id] [pages] [caller] [callee] [dest email]";
    exit();
}

# Get this files from https://github.com/PHPMailer/PHPMailer
require "class.phpmailer.php";
require "class.smtp.php";

openlog("simpleasteriskfax", LOG_PERROR, LOG_LOCAL0);

$mailTemplate = "<style>
p {
    text-align: justify;
}

table { border-collapse: collapse; }
th { border-bottom: 1px solid #CCC; border-top: 1px solid #CCC; background-color: #EEE; padding: 0.5em 0.8em; text-align: center; font-weight:bold; }
td { border-bottom: 1px solid #CCC;padding: 0.2em 0.8em; }
td+td { border-left: 1px solid #CCC;text-align: center; }
p.notify { background-color: #F0E68C; border-bottom: 1px solid #FFA500; text-align: center; padding: 5px; }
</style>
<div style='padding: 5px;'>
%body%
</div>
<div style='width:100%; border-top: 1px solid #ccc; background-color: #eee; padding: 5px;'>
SimpleAsteriskFax
</div>";

$fax_file = $argv[1];
$fax_name = $argv[2];
$fax_remoteid = $argv[3];
$fax_pages = $argv[4];
$fax_caller = $argv[5];
$fax_callee = $argv[6];
$fax_dest_email = $argv[7];

$fax_dest_dir = "/var/spool/asterisk/fax/$fax_callee";

if(file_exists($fax_file)) {
    // Convert TIFF to PDF...
    if(!file_exists($fax_dest_dir)) {
	mkdir($fax_dest_dir);
    }

    $mail = new PHPMailer;

    $mail->isSMTP();
    $mail->Host = "mailsrv.unisi.it";
    $mail->Port = 25;

    //Set who the message is to be sent from
    $mail->setFrom('fax-noreply@unisi.it', 'FAX System');

    //Set who the message is to be sent to
    $mail->addAddress($fax_dest_email);

    //Set the subject line
    $mail->Subject = "RICEVUTO FAX DA $fax_caller";

    system("tiff2pdf -o $fax_dest_dir/$fax_name.pdf -j -p A4 -t '$fax_name' $fax_file");

    if(file_exists("$fax_dest_dir/$fax_name.pdf")) {
	$message = "<p>
	    ID stazione remota: $fax_remoteid<br/>
	    Numero telefonico mittente: $fax_caller<br/>
	    Numero pagine: $fax_pages<br/>
	    Numero FAX di destinazione: $fax_callee
	</p><p>
	    Documento ricevuto in allegato.
	</p>";

	$body = str_replace(array('%body%'),array($message),$mailTemplate);

	$mail->msgHTML($body);

	//Replace the plain text body with one created manually
	$mail->AltBody = $mail->html2text($body,true);

	//Attach an image file
	$mail->addAttachment("$fax_dest_dir/$fax_name.pdf");

	//send the message, check for errors
	if (!$mail->send()) {
	    syslog(LOG_ERROR, "Mailer Error: " . $mail->ErrorInfo);
	} else {
	    syslog(LOG_NOTICE, "Sent $fax_pages page(s) fax from $fax_caller to $fax_dest_email");
	}
    } else {
	syslog(LOG_ERROR, "FATAL: cannot open converted PDF file $fax_dest_dir/$fax_name.pdf");
    }
} else {
    syslog(LOG_ERROR, "FATAL: cannot open $fax_file !");
}

?>