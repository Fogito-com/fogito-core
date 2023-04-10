
## Create

*Notes:*
> Create operation type for Activities model

*Code example:*

    <?php
        $response = CoreActivityOperations::insert([
            "operation"    => "user_edit2",
            "priority"     => 6,
            "titles"        => [
                "en"    => "User edit"
            ],
            "descriptions"        => [
                "en"    => "{user} edited profile of {whose}"
            ],
            "default_targets"   => ["mobile", "email"],
            "allowed_targets"   => ["mobile", "email"],
            "permission_keys"   => ["cards_view", "cards_checklist"],
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




## Update

*Code example:*

    <?php
        $response = CoreActivityOperations::update(
            [
                "id"    => "6303d8b2dedeb3bd6207bf0a"
            ],
            [
                "priority"     => 8,
                "titles"        => [
                    "en"    => "User edit"
                ],
                "descriptions"        => [
                    "en"    => "{user} edited profile of {whose}"
                ],
                "default_targets"   => ["mobile", "email"],
                "allowed_targets"   => ["mobile", "email"],

            ]
        );

    ?>


*Response:*

    {
        "status":"success", 
        "description":"Update successfully",
        "data": false
    }


<br/>
<br/>
<br/>