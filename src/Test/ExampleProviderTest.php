<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Paramon\ExampleClient\Dto\Comment;
use Paramon\ExampleClient\Exception\ServiceUnavailableException;
use Paramon\ExampleClient\Provider\ExampleProvider;
use PHPUnit\Framework\TestCase;

class ExampleProviderTest extends TestCase
{
    private ExampleProvider $provider;

    private MockHandler $mock;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->mock = new MockHandler([]);
        $client = new Client(['handler' => HandlerStack::create($this->mock)]);
        $httpFactory = new HttpFactory();
        $this->provider = new ExampleProvider($client, $httpFactory, $httpFactory, "http://example.com");
    }

    public function testGetComments(): void
    {
        $this->mock->append(new Response(200, body: '[{"id": 1, "name": "john doe", "text": "abc"}]'));
        $comments = $this->provider->getComments();
        $this->assertIsArray($comments);
        array_map(fn($comment) => $this->assertInstanceOf(Comment::class, $comment), $comments);
    }

    public function testErrorGetComments(): void
    {
        $this->expectException(ServiceUnavailableException::class);
        $this->mock->append(new Response(500));
        $this->provider->getComments();
    }

    public function testErrorEditComment(): void
    {
        $this->expectException(ServiceUnavailableException::class);
        $this->mock->append(new Response(500));
        $this->provider->editComment(new Comment(1, "john doe", "123"));
    }

    public function testErrorAddComment(): void
    {
        $this->expectException(ServiceUnavailableException::class);
        $this->mock->append(new Response(500));
        $this->provider->addComment(new Comment(1, "john doe", "123"));
    }
}
