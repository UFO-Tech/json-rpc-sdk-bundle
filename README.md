# UFO Tech json-rpc-sdk-bundle
![Ukraine](https://img.shields.io/badge/%D0%A1%D0%BB%D0%B0%D0%B2%D0%B0-%D0%A3%D0%BA%D1%80%D0%B0%D1%97%D0%BD%D1%96-yellow?labelColor=blue)

The Symfony bundle for simple usage Json-RPC. And automatically generation SDK from server for Symfony v.6.*

### About this package

Package for easy api creation SDK and DTO for json-rpc server

![License](https://img.shields.io/badge/license-MIT-green?labelColor=7b8185) ![Size](https://img.shields.io/github/repo-size/ufo-tech/jjson-rpc-sdk-bundle?label=Size%20of%20the%20repository) ![package_version](https://img.shields.io/github/v/tag/ufo-tech/jjson-rpc-sdk-bundle?color=blue&label=Latest%20Version&logo=Packagist&logoColor=white&labelColor=7b8185) ![fork](https://img.shields.io/github/forks/ufo-tech/jjson-rpc-sdk-bundle?color=green&logo=github&style=flat)

### Environmental requirements
![php_version](https://img.shields.io/packagist/dependency-v/ufo-tech/jjson-rpc-sdk-bundle/php?logo=PHP&logoColor=white) ![symfony_version](https://img.shields.io/packagist/dependency-v/ufo-tech/jjson-rpc-sdk-bundle/symfony/framework-bundle?label=Symfony&logo=Symfony&logoColor=white) ![symfony_version](https://img.shields.io/packagist/dependency-v/ufo-tech/jjson-rpc-sdk-bundle/symfony/serializer?label=SymfonySerializer&logo=Symfony&logoColor=white)

# What's new?

### Version 1.1
- Generation of DTO for the api response under the condition of using the [ufo-tech/json-rpc-bundle](https://packagist.org/packages/ufo-tech/json-rpc-bundle) library on the server side and configuring the server response


# Getting Started

## Automatic package installation in Symfony

### Step 0 (RECOMMENDED): Configure Composer
In order for your Symfony Flex to automatically make all the necessary settings when you add a package, you need to make the following changes to your `composer.json`

```json 
// composer.json    

// ...  
  
    "extra" : {
  
        // ...  
  
        "symfony": {
  
            // ...  
  
            "endpoint": [
                "https://api.github.com/repos/ufo-tech/recipes/contents/index.json?ref=main",
                "flex://defaults"
            ]
        }
  
        // ...  
  
    },

// ...  
  
```
More about Symfony Flex in [doc](https://symfony.com/doc/current/setup/flex_private_recipes.html)



### Step 1: Installation
From the console in the project folder, run this command to download the latest version of this package:
```shell
composer require ufo-tech/json-prc-sdk-bundle
```

### Step 2: Register the package

Make sure that the bundle is automatically registered in your project's `config/bundles.php' file:

```php
<?php
// config/bundles.php

return [
    // ...
    Ufo\JsonRpcSdkBundle\JsonRpcSdkBundle::class => ['all' => true],,
    // ...
];

```

### Step 3: Adding parameters

In ```config/packages/json_rpc_sdk.yaml``` you can configure generator, to automatically regenerate SDK when server change methods

Here is example with explanation:
```yaml
# config/packages/json_rpc_sdk.yaml
json_rpc_sdk:
    #Namespace of generated SDK. Files will be generated to folder which contain this namespace by PSR-4. 
    #Folder will be created if not exists.
    namespace: App\Sdk
    
    #List of "domain" or vendor of API server.
    vendors:
        - name: products
          url: https://products.example.com/rpc
          
        - name: orders # Required: Name of vendor namespace
          url: https://orders-api.example.com/api-rpc # Required: Url of endpoint of JsonRPC
          token_key: some-key # Optional: Name of Header token if security enabled  
          token: dsfsdfsdfsdfsdfsd32 # Optional: Value of Header token if security enabled
```
#### .ENV
You can pass tokens to access the provider api via environment variables
```configs
PRODUCT_API_TOKEN_KEY=Rpc-Security-Token
PRODUCT_API_TOKEN=048ecafe6228863c444b7320e6e943d4

ORDER_API_TOKEN_KEY=Rpc-Custom-Token
ORDER_API_TOKEN=048ecafe624x22x3er4b7320e6e943d4
```

```yaml
# config/packages/json_rpc_sdk.yaml
json_rpc_sdk:
    #...
    vendors:
        #...
        - name: products 
          url: https://products.example.com/rpc 
          token_key: %env(resolve:PRODUCT_API_TOKEN_KEY)% 
          token: %env(resolve:PRODUCT_API_TOKEN)% 

        - name: orders 
          url: https://orders-api.example.com/api-rpc 
          token_key: %env(resolve:ORDER_API_TOKEN_KEY)% 
          token: %env(resolve:ORDER_API_TOKEN)% 
```


### Step 4: Generate SDK
You have two options for generating SDK:
1. Make SDK for one vendor from JsonRPC server.
2. Generate SDK for every vendor-URL from config file

For single SDK You have to execute ```bin/console ufo:sdk:make ``` with
- vendor (required)
- RPC endpoint url (required)
- Token name (optional)
- Token value (optional)

Example:
```shell
bin/console ufo:sdk:make vendor http://api.endpoint/rpc -ttoken-name -stoken-value
```

For batch generating just run without arguments (arguments will be passed from config)
```shell
bin/console ufo:sdk:generate
```
Done! You have generated SDK.
> :warning: **NOTE! 
> 
> RPC server answer will be cached for 1 HOUR. If you need to clear it and request api again, run:**
> ```shell
> bin/console cache:clear
>```

### Step 5: Usage of SDK
After generating you are ready to use your SDK.

Here is example of generated SDK.
<details>
<summary> App\Sdk\Test\PingProcedure</summary>

```php
<?php
/**
* Auto generated SDK class for easy usage RPC API Test
* @link http://nginx/rpc
* Created at 23.07.2023 20:59:37
*
* @category ufo-tech
* @package json-rpc-client-sdk
* @generated ufo-tech/json-rpc-client-sdk
* @author Alex Maystrenko
<ashterix69@gmail.com>
* @see https://ufo-tech.github.io/json-rpc-client-sdk/ Library documentation
* @license https://github.com/ufo-tech/json-rpc-client-sdk/blob/main/LICENSE
*/
namespace App\Sdk\Test;

use Ufo\RpcSdk\Interfaces\ISdkMethodClass;
use Ufo\RpcSdk\Procedures\AbstractProcedure;
use Ufo\RpcSdk\Procedures\ApiMethod;
use Ufo\RpcSdk\Procedures\ApiUrl;
use Ufo\RpcObject\RpcResponse;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
/**
* RPC API Test
* @link http://nginx/rpc
*/
#[ApiUrl('http://nginx/rpc')]
#[AutoconfigureTag('ufo.sdk_method_class')]
class PingProcedure extends AbstractProcedure implements ISdkMethodClass
{
    /**
    * @method PingProcedure.ping
    * @return string
    */
    #[ApiMethod('PingProcedure.ping')]
    public function ping(): string 
    {
        return $this->requestApi()->getResult();
    }

}
```

</details>

Brilliant. Now let's use it. It is as simple as possible. 
In your Controllers/Procedures/Commands/e.t.c
You just have to call your SDK method from your SDK and pass any required parameters. Like that 

```php
<?php

namespace App\Controller;

use App\Sdk\Test\PingProcedure;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/test', name: 'test')]
    public function indexAction(PingProcedure $procedure): Response
    {
        $response = $procedure->ping();
        return new Response($response);
    }
}
```
### Step 6: Profit
When you call your method, this package will make RPC request with configured token, to your server with all parameters, than handle response, under the hood and return you just data which you can use. This lib working like a bridge between your code and remote RPC code just like you use it local. 