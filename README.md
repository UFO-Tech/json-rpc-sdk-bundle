# UFO Tech json-rpc-sdk-bundle
The Symfony bundle for simple usage Json-RPC. And automatically generation SDK from server


## Installation:
```shell
composer require ufo-tech/json-prc-sdk-bundle
```

## Configuration
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
          url: http://products.example.com/rpc
          
        - name: orders # Required: Name of vendor namespace
          url: http://orders-api.example.com/api-rpc # Required: Url of endpoint of JsonRPC
          token_key: some-key # Optional: Name of Header token if security enabled  
          token: dsfsdfsdfsdfsdfsd32 # Optional: Value of Header token if security enabled

```

## Usage
> :warning: **NOTE! RPC server answer will be cached for 1 HOUR. If you need to clear it and request api again, run:**
> ```shell
> bin/console cache:clear
>```

### Generate SDK:
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
bin/console ufo:sdk:make vendor http://api.endpoint/rpc token-name token-value
```

For batch generating just run without arguments (arguments will be passed from config)
```shell
bin/console ufo:sdk:generate
```
Done! You have generated SDK.

### Usage of SDK:
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
    *
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

DONE! When you call your method, this package will make RPC request with configured token, to your server with all parameters, than handle response, under the hood and return you just data which you can use. This lib working like a bridge between your code and remote RPC code just like you use it local. 