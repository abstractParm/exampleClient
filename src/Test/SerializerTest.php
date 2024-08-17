<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Test;

use Paramon\ExampleClient\Dto\Comment;
use Paramon\ExampleClient\Exception\DeserializeException;
use Paramon\ExampleClient\Serializer\SerializeGroup;
use Paramon\ExampleClient\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    public function testDeserilizationPrimitiveObject(): void
    {
        $data = [
            "testField1" => [1, 2, 3, 4],
            "testField2" => 1.12,
            "testField3" => "abc",
            "testField4" => 1,
            "id" => 1,
        ];
        $obj = Serializer::getDeserializedObject($data, new class {
            public int $id;
            public array $testField1;
            public float $testField2;
            public string $testField3;
            public float $testField4;
            public ?int $testField5;
            public string $testField6 = "123";
        });

        $this->assertIsObject($obj);
        $this->assertDataToDeserializedObject($data, $obj);
    }

    public function testDeserilizationNestedObject(): void
    {
        $data = [
            "testField1" => [1, 2, 3, 4],
            "testField2" => 1.12,
            "testField3" => "foo",
            "testField4" => 1,
            "id" => 1,
            "comment" => [
                "id" => 2,
                "name" => "john doe",
                "text" => "cbd"
            ]
        ];
        $obj = Serializer::getDeserializedObject($data, new class {
            public int $id;
            public array $testField1;
            public float $testField2;
            public string $testField3;
            public float $testField4;
            public Comment $comment;
        });
        unset($data["comment"]);
        $this->assertIsObject($obj);
        $this->assertInstanceOf(Comment::class, $obj->comment);
        $this->assertDataToDeserializedObject($data, $obj);
    }

    public function testDeserializationWithEmptyData(): void
    {
        $this->expectException(DeserializeException::class);
        $data = [];
        Serializer::getDeserializedObject($data, new class {
            public int $id;
            public array $testField1;
            public float $testField2;
            public string $testField3;
            public float $testField4;
            public Comment $comment;
        });
    }

    public function testSerialization(): void
    {
        $comment = new Comment(1, "john doe", "abc");
        $expectedData = [
            "id" => 1,
            "name" => "john doe",
            "text" => "abc",
        ];
        $this->assertEquals(Serializer::serialize($comment), $expectedData);
    }

    public function testNestedSerialization(): void
    {
        $object = new class {
            public int $id = 1;
            public array $testField1 = [];
            public float $testField2 = 1;
            public string $testField3 = "";
            public float $testField4 = 1.5;
            public ?Comment $comment = null;
        };
        $object->comment = new Comment(1, "john doe", "abc");
        $expectedData = [
            "id" => 1,
            "testField1" => [],
            "testField2" => 1,
            "testField3" => "",
            "testField4" => 1.5,
            "comment" => [
                "id" => 1,
                "name" => "john doe",
                "text" => "abc",
            ],
        ];
        $this->assertEquals(Serializer::serialize($object), $expectedData);
    }

    public function testGroupSerialization(): void
    {
        $object = new class {
            public int $id = 1;
            #[SerializeGroup(["content", "content1"])]
            public array $testField1 = [];
            #[SerializeGroup(["content1"])]
            public float $testField2 = 1;
            #[SerializeGroup(["content"])]
            public string $testField3 = "";
            public float $testField4 = 1.5;
            #[SerializeGroup(["public"])]
            public ?Comment $comment = null;
        };
        $object->comment = new Comment(1, "john doe", "abc");
        $expectedData = [
            "testField1" => [],
            "testField3" => "",
            "comment" => [
                "name" => "john doe",
                "text" => "abc",
            ],
        ];
        $this->assertEquals(Serializer::serialize($object, ["public", "content"]), $expectedData);
    }

    private function assertDataToDeserializedObject(array $data, object $obj): void
    {
        foreach ((array) $obj as $key => $value) {
            if (!$origvalue = ($data[$key] ?? null)) {
                continue;
            }
            if (is_object($value)) {
                $this->assertDataToDeserializedObject($origvalue, $value);
            }
            $this->assertEquals($origvalue, $value);
        }
    }
}
