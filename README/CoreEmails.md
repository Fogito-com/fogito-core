
## Insert
> SEND EMAIL

*Code:*

    <?php
        $data = CoreEmails::insert([
            "user_id"             => "2i23u4i23ui2i12uiu2nm", // OPTIONAL
            "subject"             => "Test subject",
            "from"                => "fromEmail@fromdomain.com", // OPTIONAL, default: noprely@fogito.com
            "from_title"          => "From Email Title", // OPTIONAL
            "to"                  => "toEmail@todomain.com",
            "reply_to"            => "noreply@fogito.com", // OPTIONAL
            "body"                => "Hello world!", // TEXT or HTML
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