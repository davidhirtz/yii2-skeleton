<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Xml;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Xml\XmlNode;
use yii\base\InvalidArgumentException;

class XmlNodeTest extends TestCase
{
    public function testTextIsTrimmed(): void
    {
        $node = XmlNode::fromString('<root>  Hello  </root>');

        self::assertSame('root', $node->name);
        self::assertSame('Hello', $node->text);
        self::assertSame([], $node->attributes);
        self::assertSame([], $node->children);
    }

    public function testEmptyTextIsNull(): void
    {
        self::assertNull(XmlNode::fromString('<root>   </root>')->text);
        self::assertNull(XmlNode::fromString('<root/>')->text);
    }

    public function testAttributes(): void
    {
        $node = XmlNode::fromString('<root id="1" name=" Test "/>');

        self::assertSame(['id' => '1', 'name' => 'Test'], $node->attributes);
        self::assertSame('1', $node->getAttribute('id'));
        self::assertNull($node->getAttribute('missing'));
    }

    public function testChildrenAreGroupedByName(): void
    {
        $node = XmlNode::fromString(<<<XML
            <root>
                <item>1</item>
                <item>2</item>
                <other/>
            </root>
            XML);

        self::assertSame(['item', 'other'], array_keys($node->children));
        self::assertCount(2, $node->getChildren('item'));
        self::assertSame('2', $node->getChildren('item')[1]->text);
        self::assertSame('1', $node->getChild('item')?->text);
        self::assertNull($node->getChild('missing'));
        self::assertSame([], $node->getChildren('missing'));
    }

    public function testNestedChildren(): void
    {
        $node = XmlNode::fromString('<root><a><b value="1">Text</b></a></root>');
        $child = $node->getChild('a')?->getChild('b');
        self::assertNotNull($child);

        self::assertSame('b', $child->name);
        self::assertSame('Text', $child->text);
        self::assertSame('1', $child->getAttribute('value'));
    }

    public function testNamespacedAttributesAndChildrenKeepTheirPrefix(): void
    {
        $node = XmlNode::fromString(<<<XML
            <rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
                <item dc:type="Text">
                    <dc:creator>Test</dc:creator>
                    <title>Title</title>
                </item>
            </rss>
            XML);

        self::assertSame(['version' => '2.0'], $node->attributes);

        $item = $node->getChild('item');
        self::assertNotNull($item);

        self::assertSame(['dc:type' => 'Text'], $item->attributes);
        self::assertSame('Test', $item->getChild('dc:creator')?->text);
        self::assertSame('Title', $item->getChild('title')?->text);
    }

    /**
     * A default namespace is read by the empty prefix, so its children must not be prefixed with it either.
     */
    public function testDefaultNamespace(): void
    {
        $node = XmlNode::fromString(<<<XML
            <feed xmlns="http://www.w3.org/2005/Atom">
                <entry><title>Title</title></entry>
            </feed>
            XML);

        self::assertSame('Title', $node->getChild('entry')?->getChild('title')?->text);
    }

    public function testToArray(): void
    {
        $node = XmlNode::fromString('<root id="1"><item>Text</item></root>');

        self::assertSame([
            'name' => 'root',
            'text' => null,
            'attributes' => ['id' => '1'],
            'children' => [
                'item' => [
                    [
                        'name' => 'item',
                        'text' => 'Text',
                        'attributes' => [],
                        'children' => [],
                    ],
                ],
            ],
        ], $node->toArray());
    }

    public function testInvalidXmlThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        XmlNode::fromString('<root>');
    }

    public function testInvalidXmlDoesNotLeakLibxmlErrors(): void
    {
        try {
            XmlNode::fromString('<root>');
        } catch (InvalidArgumentException) {
        }

        self::assertSame([], libxml_get_errors());
    }
}
