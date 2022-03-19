# Fogito Core
Fogito Core - MVC structure

## Installation

```
composer require fogito/core
```

## Structure

This a multi-module [MVC][mvc-pattern] structure. This example implements two modules: frontend and backend.
By default frontend is served if no route to backend is asked. You can define which routes
use one module or another in `public/index.php`:

`
project/
├── apps
│   ├── backend
│   │   ├── Module.php
│   │   ├── controllers
│   │   │   ├── IndexController.php
│   │   │   ├── LoginController.php
│   │   │   └── ProductsController.php
│   │   └── models
│   │       └── Products.php
│   └── frontend
│       ├── Module.php
│       ├── controllers
│       │   ├── IndexController.php
│       │   ├── ProductsController.php
│       │   └── UsersController.php
│       └── models
│           └── Products.php
└── public
    └── index.php
`

## Team

- <a href="https://github.com/senior2ral">Tural Ilyasov</a><br/>
- <a href="https://github.com/seniorshahmar">Shahmar Shukurov</a><br/>

## Links
- <a href="https://fogito.com">Website</a><br/>

## License info

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