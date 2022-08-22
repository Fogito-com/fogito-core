<p align="center">
  <a href="https://app.fogito.com" rel="noopener noreferrer nofollow" target="blank">
   <img width="200" src="https://app.fogito.com/assets/images/logos/logo.svg" alt="Fogito Core MVC">
  </a>
</p>

<h1 align="center">Fogito Core MVC</h1>

## 📦 Installation

```
composer require fogito/core
```

## 🔨 Structure

This a multi-module [MVC][mvc-pattern] structure.

```
app
    ├── config
    │   └── config.php
    │
    ├── lib
    │   └── Cache.php
    │   └── Helpers.php
    │
    ├── middlewares
    │   └── Api.php
    │
    ├── models
    │   ├── Products.php
    │   └── Users.php
sites
    └── api
        ├── config (OPTIONAL)
        │   ├── routes.php
        │   └── config.php
        │   
        ├── modules
        │   ├── auth
        │   │   ├── controllers
        │   │   │   ├── LoginController.php
        │   │   │   └── RegisterController.php
        │   │   └── Module.php
        │   │   
        │   └── products
        │       ├── controllers
        │       │   ├── CreateController.php
        │       │   ├── ListController.php
        │       │   └── DeleteController.php
        │       └── Module.php
        │      
        └── public
            ├── index.php
            ├── assets
```

## ⚙️ Models
- <a href="https://github.com/Fogito-com/fogito-core/blob/master/README/CoreUsers.md">🙍‍ Core Users</a><br/>
- <a href="https://github.com/Fogito-com/fogito-core/blob/master/README/CoreFiles.md">📦 Core Files</a><br/>
- <a href="https://github.com/Fogito-com/fogito-core/blob/master/README/CoreNotifications.md">⏰ Core Notifications</a><br/>
- <a href="https://github.com/Fogito-com/fogito-core/blob/master/README/CoreEmails.md">✉️ Core Emails</a><br/>
- <a href="https://github.com/Fogito-com/fogito-core/blob/master/README/CoreActivities.md">🔍 Core Activities</a><br/>
- <a href="https://github.com/Fogito-com/fogito-core/blob/master/README/CoreActivities.md">🔍 Core Activity Operations</a><br/>

## 🌍 License 

Fogito Core License

Copyright (c) 2022 Fogito

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.