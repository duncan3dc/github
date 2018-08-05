---
layout: default
title: Setup
permalink: /setup/
api: Api
---

All classes are in the `duncan3dc\GitHub` namespace.  

To get started, you'll need the ID of your [GitHub App](https://github.com/settings/apps), and the private key.  

~~~php
use duncan3dc\GitHub\Api;

$key = file_get_contents(__DIR__ . "/github-api-key.pem");
$api = new Api(99999, $key);
foreach ($api->getOrganizations() as $organization) {
    echo $organization->getName() . "\n";
}
~~~


