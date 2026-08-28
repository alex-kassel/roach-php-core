<h1 align="center">🕷️ Roach PHP Core</h1>

<p align="center">
  <strong>Web scraping and crawling toolkit for PHP. Community fork of roach-php/core with on-demand lazy DOM loading.</strong>
</p>

<p align="center">
  <a href="#key-features">Key Features</a> •
  <a href="#requirements">Requirements</a> •
  <a href="#installation">Installation</a> •
  <a href="#usage">Usage</a> •
  <a href="#testing">Testing</a> •
  <a href="#credits">Credits</a> •
  <a href="CHANGELOG.md">Changelog</a>
</p>

<p align="center">
  <a href="RELEASE-GATE.md"><img src="https://img.shields.io/badge/Audit-Verified-10b981?logo=shield" alt="Audit Verified"></a>
  <a href="https://packagist.org/packages/alex-kassel/roach-php-core"><img src="https://img.shields.io/packagist/v/alex-kassel/roach-php-core?color=f59e0b&logo=packagist&logoColor=white" alt="Latest Version"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777bb4?logo=php&logoColor=white" alt="PHP Support"></a>
  <a href="phpstan.neon"><img src="https://img.shields.io/badge/PHPStan-Level%209-8b5cf6?logo=php&logoColor=white" alt="PHPStan Level 9"></a>
</p>

---

> [!NOTE]
> **Community Fork**  
> This package is a fork of the original [`roach-php/core`](https://github.com/roach-php/core) created by [Kai Sassnowski](https://github.com/kaisassnowski).
>
> *As described by the original author:*  
> *"Roach is a complete web scraping toolkit for PHP. It is heavily inspired (read: a shameless clone) of the popular [Scrapy](https://scrapy.org) package for Python."*
>
> **Improvements in this Fork:**
> - **Lazy `DomCrawler` Initialization:** The HTML DOM Crawler is instantiated on demand instead of eagerly on every HTTP response, reducing memory usage for API/JSON endpoints and large crawl runs.
> - **Native JSON Response Support:** Added a `$response->json()` helper method for strict decoding of JSON responses.
> - **Modern Component Compatibility:** Extended constraints supporting Symfony 7/8 components and Guzzle 7/8.
>
> 📖 **Original Documentation:** [https://roach-php.dev](https://roach-php.dev)

---

## Key Features

* **Modular Spider Architecture:** Define elegant spiders with granular lifecycle hooks and declarative pipeline stages.
* **Extensible Middleware Pipeline:** Intercept and modify outgoing HTTP requests and incoming HTTP responses via pluggable middleware.
* **Streamlined Item Pipelines:** Transform, validate, and persist scraped data using composable item processors.
* **Interactive CLI Shell:** Rapidly prototype and test CSS/XPath extraction selectors live from the interactive `roach` shell.
* **Robots.txt & Concurrency Safety:** Built-in rate limiting, concurrency scheduling, and automatic `robots.txt` compliance out of the box.

---

## Requirements

* **PHP:** 8.2+ (tested on PHP 8.2, 8.3, 8.4, and 8.5)
* **Extensions:** `ext-dom`, `ext-libxml`, `ext-mbstring`
* **HTTP Client:** Guzzle 7.8+ or 8.0+

---

## Installation

Install the package via Composer:

```bash
composer require alex-kassel/roach-php-core
```

---

## Usage

Define a spider extending `BasicSpider`:

```php
use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;

class NewsSpider extends BasicSpider
{
    public array $startUrls = [
        'https://example.com/news',
    ];

    /**
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        $headlines = $response->filter('article h2.title')->each(function ($node) {
            return $node->text();
        });

        foreach ($headlines as $headline) {
            yield $this->item(['title' => $headline]);
        }
    }
}
```

Run the spider using the Roach engine:

```php
use RoachPHP\Roach;

// Start scraping run
Roach::startSpider(NewsSpider::class);
```

---

## Testing

From the monorepo root, run the complete package verification pipeline:

```bash
composer pkg:check alex-kassel/roach-php-core --json
```

---

## Credits

This package is a modern community fork and continuation of the original `roach-php/core` created by **Kai Sassnowski**.

* **Kai Sassnowski** ([@kaisassnowski](https://github.com/kaisassnowski)) — Original Author & Architecture
* **Alexander Macenko** ([@alex-kassel](https://github.com/alex-kassel)) — Fork Maintainer & Modern PHP/Laravel Compatibility
* **All Contributors** — Thanks to all Open Source contributors who have contributed to Roach PHP

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

---

## Security Vulnerabilities

Please review [Security Policies](https://github.com/alex-kassel/roach-php-core/security/policy) on how to report vulnerabilities.

---

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
