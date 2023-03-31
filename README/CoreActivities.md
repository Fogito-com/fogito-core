
## Create

*lang description notes:*
> Lang description example: "{who} uploaded file {filename} on card {card}" \
> {who} - is default replacement and will be replaced by executor (user_id). No need add to replacements key

*Code example 1:*

    <?php
        $data = CoreActivities::insert([
            "user_id"      => "2i23u4i23ui2i12uiu2nm", // Executed by Whom
            "user_ids"     => ["2i23u4i23ui2i12uiu2nm", "14i23u4i23ui2i12uiu29o"], // Assigned users to activity
            "company_id"   => "5k324i23ui2i12uim94l3", // If user is empty, company_id is required
            "company_ids"  => ["5k324i23ui2i12uim94l3", "1a324i23u32i15uimd23l2"], // optional, assigned all companies
            "app_ids"      => [201, 205], // optional, assigned all microservices. default: app id of your microservice
            "operation"    => "card_create",
            "default_description"     => "{who} uploaded file {filename} on card {card}", // if lang is not registered on core, this desciprtion will be used
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
            "priority"     => 4, // default: 5, range: 1/10, range: 1/10, 1-3 = Low, 4-7 = Medium, 8-10 Critic
            "created_at"     => 14928832983, // Unixtime
        ]);
    ?>



*Code example 2:*

    <?php
        $data = CoreActivities::insert([
            "user_id"      => "2i23u4i23ui2i12uiu2nm", // Executed by Whom
            "user_ids"     => ["2i23u4i23ui2i12uiu2nm", "14i23u4i23ui2i12uiu29o"], // Assigned users to activity
            "operation"    => "card_create",
            "replacements" => [
                [
                    "key"       => "whose",
                    "type"      => "user",
                    "id"        => "6218e1e4dd93085d646017de",
                    "title"     => "Firstname Lastname",
                    "is_link"   => 1
                ]
            ], // default: false
            "filters" => [
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


## Find
> Find Activities

*Code:*

    <?php
        $data = CoreActivities::find([
            [
                "filters.card" => "52k3i42u3i4u3i24mw2g"
            ],
            "skip"      => 0,
            "limit"     => 2,
        ]);
    ?>


*Response:*

    {
        "status":"success", 
        "description":"",
        "data": []
    }


<br/>
<br/>
<br/>


