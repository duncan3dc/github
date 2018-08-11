# github
A PHP library to interact with the GitHub API

Full documentation is available at http://duncan3dc.github.io/github/  
PHPDoc API documentation is also available at [http://duncan3dc.github.io/github/api/](http://duncan3dc.github.io/github/api/namespaces/duncan3dc.GitHub.html)  

[![release](https://poser.pugx.org/duncan3dc/github/version.svg)](https://packagist.org/packages/duncan3dc/github)
[![build](https://travis-ci.org/duncan3dc/github.svg?branch=master)](https://travis-ci.org/duncan3dc/github)
[![coverage](https://codecov.io/gh/duncan3dc/github/graph/badge.svg)](https://codecov.io/gh/duncan3dc/github)


## Installation

The recommended method of installing this library is via [Composer](https://getcomposer.org/).

Run the following command from your project root:

```bash
$ composer require duncan3dc/github
```


## Getting Started

```php
use duncan3dc\GitHub\Api;

require __DIR__ . "/vendor/autoload.php";

# Connect to a GitHub app using the private key
$api = new Api(1014, file_get_contents("/var/safe/github.pem"));

# List all the organizations this app is installed under
foreach ($app->getOrganizations() as $organization) {
    echo $organization->getName() . "\n";
}

# Get a specific organization/user
$organization = $app->getOrganization("duncan3dc");
```

_Read more at http://duncan3dc.github.io/github/_  


## Where to get help
Found a bug? Got a question? Just not sure how something works?  
Please [create an issue](//github.com/duncan3dc/github/issues) and I'll do my best to help out.  
Alternatively you can catch me on [Twitter](https://twitter.com/duncan3dc)
