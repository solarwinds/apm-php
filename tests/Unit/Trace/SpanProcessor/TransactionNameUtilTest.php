<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\SpanProcessor;

use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\SpanProcessor\TransactionNameUtil;

class TransactionNameUtilTest extends TestCase
{
    public function test_resolve_transaction_name_unknown(): void
    {
        $this->assertSame('unknown', TransactionNameUtil::resolveTransactionName('http://:80')); // Malformed URL
    }

    public function test_resolve_transaction_name(): void
    {
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('http://www.boost.org/index.html'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('http://www.boost.org/index.html?field=value'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('http://www.boost.org/index.html?field=value#downloads'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('http://www.boost.org:80/index.html?field=value#downloads'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('https://www.boost.org/index.html'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('https://www.boost.org/index.html?field=value'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('https://www.boost.org/index.html?field=value#downloads'));
        $this->assertSame('/index.html', TransactionNameUtil::resolveTransactionName('https://www.boost.org:80/index.html?field=value#downloads'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('https://example.com'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('https://example.com:8080'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('https://example.com/'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('https://example.com:8080/'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('ftp://example.com'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('ftp://example.com:8000'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('sftp://example.com'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('sftp://example.com:8000'));
        $this->assertSame('/1/2', TransactionNameUtil::resolveTransactionName('http://www.boost.org/1/2/3/4/5/index.html?field=value#downloads'));
        $this->assertSame('/1/2', TransactionNameUtil::resolveTransactionName('http://www.boost.org:8000/1/2/3/4/5/index.html?field=value#downloads'));
        $this->assertSame('/1/a.html', TransactionNameUtil::resolveTransactionName('https://user:pass@example.com/1/a.html'));
        $this->assertSame('/1/a.html', TransactionNameUtil::resolveTransactionName('https://@example.com/1/a.html'));
        $this->assertSame('/1/a.html', TransactionNameUtil::resolveTransactionName('https://user@example.com/1/a.html'));
        $this->assertSame('/1/a.html', TransactionNameUtil::resolveTransactionName('https://:pass@example.com/1/a.html'));
        $this->assertSame('/1/a.html', TransactionNameUtil::resolveTransactionName('https://:@example.com/1/a.html'));
        $this->assertSame('/a', TransactionNameUtil::resolveTransactionName('a'));
        $this->assertSame('/a', TransactionNameUtil::resolveTransactionName('/a'));
        $this->assertSame('/a/b', TransactionNameUtil::resolveTransactionName('/a/b'));
        $this->assertSame('/', TransactionNameUtil::resolveTransactionName('/'));
        $this->assertSame('/images/dot.gif', TransactionNameUtil::resolveTransactionName('images/dot.gif?v=hide#a'));
    }
}
