
## Create

*lang_key notes:*
> You have to register lang_keys on core. \
> Lang description example: "{who} uploaded file {filename} on card {card}" \
> {who} - is default replacement and will be replaced by executor (user_id). No need add to replacements key

*Code:*

    <?php
        $data = CoreActivities::insert([
            "user_id"      => "2i23u4i23ui2i12uiu2nm", // Executed by Whom
            "user_ids"     => ["2i23u4i23ui2i12uiu2nm", "14i23u4i23ui2i12uiu29o"], // Assigned users to activity
            "operation"    => "card_create",
            "lang_key"     => "lang_key", // {who} uploaded file {filename} on card {card}
            "replacements" => [
                [
                    "key"       => "filename",
                    "type"      => "file",
                    "id"        => "23i42u3i4u3i23i4uhe3",
                    "title"     => "ScreeenShot.jpg",
                    "is_link"   => 0
                ],
                [
                    "key"       => "card",
                    "type"      => "card",
                    "id"        => "52k3i42u3i4u3i24mw2g",
                    "title"     => "Test task",
                    "is_link"   => 1
                ]
            ], // default: false
            "filters" => [
                "card"          => "52k3i42u3i4u3i24mw2g",
                "file_id"       => "352k3i42u3i4u3i24mi92"
            ], 
            "priority"     => 4 // default: 5, range: 1/10, range: 1/10, 1-3 = Low, 4-7 = Medium, 8-10 Critic
        ]);
    ?>

*Response:*

    {
        "status":"success", 
        "description":"Inserted successfully",
        "data": {
            "id"    => "12312i3j12kl3j1"
        }
    }


<br/>
<br/>
<br/>