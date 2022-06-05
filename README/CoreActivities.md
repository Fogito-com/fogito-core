
## Create
> Create activity

*Code:*

    <?php
        $data = CoreActivities::insert([
            "user_id"      => "2i23u4i23ui2i12uiu2nm", // Executed by Whom
            "user_ids"     => ["2i23u4i23ui2i12uiu2nm"], // Assigned users to activity
            "operation"    => "card_create",
            "lang_key"     => "lang_key", // Lang key for description
            "replacements" => [
                [
                    "key"       => "from",
                    "type"      => "user",
                    "id"        => "23i42u3i4u3i23i4uhe3",
                    "title"     => "Firstname Lastname",
                    "is_link"   => 1
                ],
                [
                    "key"       => "card",
                    "type"      => "card",
                    "id"        => "52k3i42u3i4u3i24mw2g",
                    "title"     => "Test task",
                    "is_link"   => 1
                ]
            ], // default: false
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