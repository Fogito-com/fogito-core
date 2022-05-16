
## Insert
> SEND EMAIL

*Code:*

    <?php
        $data = CoreNotifications::insert([
            "user_id"      => "2i23u4i23ui2i12uiu2nm", // OPTIONAL
            "title"        => "Fogito LLC", // Optional
            "message"      => "Hello, dear user",
            "expire"       => 60, // OPTIONAL, if cannot be sent during 60 seconds, will be deleted. default: 0, no expiration
            "silent"       => false, // default: false
            "app_ids"      => [601],
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