
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
            "activity_id"  => false, // default: false, if this notification is sent for activity, please assign activity_id
            "priority"     => false, // default: 5, range: 1/10, 1-3 = Low, 4-7 = Medium, 8-10 Critic
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