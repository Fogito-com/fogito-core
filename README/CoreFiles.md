
## Find
> Find Files

*Code:*

    <?php
        $data = CoreFiles::find([
            [
                "id"    => [
                    '$in' => ["5e68dcb5a828d70794008642", "5e80bd6cdc87235bc4078e46"]
                ]
            ],
            //"columns"   => [],  returns all columns by default
            "skip"      => 0,
            "limit"     => 2
        ]);
    ?>

*Response:*

    [
        {
            "id": "5e68dcb5a828d70794008642",
            "avatars": {
                "tiny": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/g9x4y3f85tjcuk9ywcw5bn25x0vsx99v\/129awa9i4e2jq1luvp2s978nmkn0hiqi\/50x50.jpg",
                "small": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/a4lq1dgdkvjqslldakkf4t0lg6n89zjc\/06ev3fkpmb92pg18a80cpkxmqt16z1bz\/120x120.jpg",
                "medium": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/0b2diwfukkjmw320f5q0xbbmkc4n1uu3\/i9oawvtlb5h361emw4002kg8ygll9g3d\/320x320.jpg",
                "large": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/ugwr8sz6krw58qc5hppks87w4f9r1ak7\/bus3b41qaok1zoelw6dr5e1z7zvev3n2\/800x800.jpg"
            },
            "type": "jpg",
            "size": 180134,
            "url": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/wsb4wxhfwfhdprl21ilpa9l0\/v6zbhsuzsf0ppxmd4eo3ixlz\/360_f_279826857_thotadb7a6nasxascbtkw7jufx4qly45.jpg"
        },
        {
            "id": "5e80bd6cdc87235bc4078e46",
            "avatars": {
                "tiny": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/g9x4y3f85tjcuk9ywcw5bn25x0vsx99v\/129awa9i4e2jq1luvp2s978nmkn0hiqi\/50x50.jpg",
                "small": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/a4lq1dgdkvjqslldakkf4t0lg6n89zjc\/06ev3fkpmb92pg18a80cpkxmqt16z1bz\/120x120.jpg",
                "medium": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/0b2diwfukkjmw320f5q0xbbmkc4n1uu3\/i9oawvtlb5h361emw4002kg8ygll9g3d\/320x320.jpg",
                "large": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/ugwr8sz6krw58qc5hppks87w4f9r1ak7\/bus3b41qaok1zoelw6dr5e1z7zvev3n2\/800x800.jpg"
            },
            "type": "jpg",
            "size": 180134,
            "url": "https:\/\/fogito.s3.eu-central-1.amazonaws.com\/user\/8vgzzo9zrgev13ai\/wsb4wxhfwfhdprl21ilpa9l0\/v6zbhsuzsf0ppxmd4eo3ixlz\/360_f_279826857_thotadb7a6nasxascbtkw7jufx4qly45.jpg"
        }
    ]





<br/>
<br/>
<br/>





## Check Temp File

*Code:*

    <?php
        // Check singe temp file
        CoreFiles::checkTempFile("624c4c93255e6975046e0165");

        // Check multiple temp files
        CoreFiles::checkTempFiles(["624c4c93255e6975046e0165", "624c4d4c050b375735038654"]);
    ?>

*Response:*

    {
        "status":"success", 
        "description":"Created successfully",
        "data": [
            // here returns files
        ]
    }





<br/>
<br/>
<br/>






## Move Temp File to Real server

*Code:*

    <?php
        // Move singe temp file
        CoreFiles::moveFile("624c4c93255e6975046e0165", ["parent_type" => "album", "parent_id" => "1q2w3e4r5t6y5t6y7u"]);
        
        // Move multiple temp files
        CoreFiles::moveFiles(["624c4c93255e6975046e0165", "624c4d4c050b375735038654"], ["parent_type" => "album", "parent_id" => "1q2w3e4r5t6y5t6y7u"]);
    ?>

*Response:*

    {
        "status":"success", 
        "description":"Created successfully",
        "data": [
            // here returns files
        ]
    }





<br/>
<br/>
<br/>




## Delete
> Delete Files

*Code:*

    <?php
        $data = CoreFiles::deleteRaw(
            [
                "id"    => [
                    '$in' => ["5e68dcb5a828d70794008642", "5e80bd6cdc87235bc4078e46"]
                ]
            ]
        );
    ?>

*Response:*

    {
        "status":"success", 
        "description":"Deleted successfully"
    }






<br/>
<br/>
<br/>



