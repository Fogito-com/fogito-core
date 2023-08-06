
## Insert
> SEND SMS

*Code:*

    <?php
        $data = CoreEmails::insert([
            "user_id"             => "2i23u4i23ui2i12uiu2nm", // If phone is empty, user_id will be required 
            "phone"               => "+123482938492", // If user_id is empty, phone will be required 
            "body"                => "Hello world!", // TEXT or HTML
            "filters"             => ["card" => "2k3j4k23j4k2j3k23k4j"], // IDs for filtering sms log
            "calback"             => "https://yourCallbackUrl", // OPTIONAL: status of emails will be sent to this link
            "expire"              => 120, // seconds, 0 is no expiration
        ]);
    ?>

*Response:*

    {
        "status":"success", 
        "description":"Sent successfully",
        "data": {
            "id"    => "12312i3j12kl3j1"
        }
    }


<br/>
<br/>
<br/>