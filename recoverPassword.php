<?php
/*=========================================================================

  Program:   CDash - Cross-Platform Dashboard System
  Module:    $Id$
  Language:  PHP
  Date:      $Date$
  Version:   $Revision$

  Copyright (c) 2002 Kitware, Inc.  All rights reserved.
  See Copyright.txt or http://www.cmake.org/HTML/Copyright.html for details.

     This software is distributed WITHOUT ANY WARRANTY; without even
     the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
     PURPOSE.  See the above copyright notices for more information.

=========================================================================*/
include("cdash/config.php");
require_once("cdash/pdo.php");
include_once("cdash/common.php");
include_once("cdash/version.php");
require_once("cdash/cdashmail.php");

$db = pdo_connect("$CDASH_DB_HOST", "$CDASH_DB_LOGIN", "$CDASH_DB_PASS");
pdo_select_db("$CDASH_DB_NAME", $db);

$xml = begin_XML_for_XSLT();
$xml .= "<title>Recover password</title>";
if (isset($CDASH_NO_REGISTRATION) && $CDASH_NO_REGISTRATION==1) {
    $xml .= add_XML_value("noregister", "1");
}

@$recover = $_POST["recover"];
if ($recover) {
    $email = pdo_real_escape_string($_POST["email"]);
    $emailResult = pdo_query("SELECT id FROM ".qid("user")." where email='$email'");
    add_last_sql_error("recoverPassword");

    if (pdo_num_rows($emailResult) == 0) {
        $xml .= "<warning>This email is not registered.</warning>";
    } else {
        // Create a new password
        $keychars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&";
        $length = 10;

        $password = "";
        $max=strlen($keychars)-1;
        for ($i=0;$i<=$length;$i++) {
            // random_int is available in PHP 7 and the random_compat PHP 5.x
            // polyfill included in the Composer package.json dependencies.
            $password .= substr($keychars, random_int(0, $max), 1);
        }

        $currentURI = get_server_URI();
        $url = $currentURI."/user.php";

        $text = "Hello,\n\n You have asked to recover your password for CDash.\n\n";
        $text .= "Your new password is: ".$password."\n";
        $text .= "Please go to this page to login: ";
        $text .= "$url\n";
        $text .= "\n\nGenerated by CDash";

        if (cdashmail("$email", "CDash password recovery", $text,
       "From: CDash <".$CDASH_EMAIL_FROM.">\nReply-To: ".$CDASH_EMAIL_REPLY."\nContent-type: text/plain; charset=utf-8\nX-Mailer: PHP/" . phpversion()."\nMIME-Version: 1.0")) {
            $md5pass = md5($password);
      // If we can send the email we update the database
      pdo_query("UPDATE ".qid("user")." SET password='$md5pass' WHERE email='$email'");
            add_last_sql_error("recoverPassword");

            $xml .= "<message>A confirmation message has been sent to your inbox.</message>";
        } else {
            $xml .= "<warning>Cannot send recovery email</warning>";
        }
    }
}

$xml .= "</cdash>";

// Now doing the xslt transition
generate_XSLT($xml, "recoverPassword");
