









## Find
> Find Users

*Code:*

    <?php
        $data = CoreUsers::find([
            [
                "id"    => [
                    '$in' => ["5e68dcb5a828d70794008642", "5e80bd6cdc87235bc4078e46"]
                ]
            ],
            "columns"   => ["id", "avatar", "fullname", "firstname", "lastname"],
            "skip"      => 0,
            "limit"     => 2
        ]);
    ?>

*Response:*

    [
        {
            "id":"5fe8956bdbe82e71982084f9",
            "avatar":"https:\/\/fls01.fogito.com\/fwcQguMd\/23j4l2k3jl4k23\/42i3u4i23u4io232i3u\/50.jpg",
            "avatar_custom":true,
            "fullname":"Muslim Ragimov",
            "firstname":"Muslim",
            "lastname":"Ragimov",
        },
        {
            "id":"5fe8956bdbe82e71982084f9",
            "avatar":"https:\/\/fls01.fogito.com\/fwcQguMd\/23j4l2k3jl4k23\/42i3u4i23u4io232i3u\/50.jpg",
            "avatar_custom":true,
            "fullname":"Muslim Ragimov",
            "firstname":"Muslim",
            "lastname":"Ragimov",
        }
    ]





<br/>
<br/>

## Create Token
> To create token for user by <font color="#fff">user id</font>

*Code:*

    <?php
        $response = CoreUsers::createToken($user_id, $expire_seconds);
        if($response["status"] == "success"){ 
            $token = $response["data"]["token"];
        }
    ?>

*Response:*

    {
        "status":"success", 
        "description":"", 
        "data": {
            "token":"e63a28N741Lexej4Cdw0Z2u8n0vf6RbW3n1x83cPaIey0y900V8ycufPfN3f9o051y812_87d0c3fb10ba7278cee9100f19648b6800b73f6d"
        }
    }
