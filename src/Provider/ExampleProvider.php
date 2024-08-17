<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Provider;

use Paramon\ExampleClient\Dto\Comment;
use Paramon\ExampleClient\Exception\ServiceUnavailableException;
use Paramon\ExampleClient\Serializer\Serializer;
use Psr\Http\Message\ResponseInterface;

class ExampleProvider extends AbstractProvider
{
    /**
     * @return Comment[]
     */
    public function getComments(): array
    {
        $this->validateResponse($response = $this->sendRequest("GET", "/comments", [self::HEADERS => [
            "Accept" => "application/json",
        ]]));

        return array_map(
            fn(array $commentData) => Serializer::getDeserializedObject($commentData, Comment::class),
            $this->getParsedBody($response)
        );
    }

    public function addComment(Comment $comment): void
    {
        $this->validateResponse($this->sendRequest("POST", "/comment", [
            self::BODY => Serializer::serialize($comment),
            self::HEADERS => [
                "Content-type" => "application/json",
            ]
        ]));
    }

    public function editComment(Comment $comment): void
    {
        $this->validateResponse($this->sendRequest("PUT", "/comment/{$comment->id}", [
            self::BODY => Serializer::serialize($comment, ["content"]),
            self::HEADERS => [
                "Content-type" => "application/json",
            ]
        ]));
    }

    protected static function getHost(): string
    {
        return "http://example.com";
    }

    private function validateResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new ServiceUnavailableException();
        }
    }
}
