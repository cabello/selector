<?php

use Snit\Selector;

class BooksSelectorTest extends PHPUnit_Framework_TestCase
{
    public function getBookDataInJsonString()
    {
        $json = '{
            "books": [
                {
                    "isbn": "abc123",
                    "title": "Abc",
                    "authors": ["John", "Kevin"]
                },
                {
                    "isbn": "def456",
                    "title": "Def",
                    "authors": ["Kevin"]
                },
                {
                    "isbn": "ghi789",
                    "title": "Ghi",
                    "authors": ["Stuart"]
                }
            ]
        }';

        return $json;
    }

    public function getBookDataInStdClass()
    {
        return json_decode($this->getBookDataInJsonString());
    }

    public function setUp()
    {
        $this->selector = new Selector($this->getBookDataInStdClass());
    }

    public function testFindOneFinds()
    {
        $result = $this->selector->findOne('books', 'title', 'Def');

        $this->assertInstanceOf('\StdClass', $result);

        $this->assertEquals('def456', $result->isbn);
        $this->assertEquals('Def', $result->title);
        $this->assertEquals(array('Kevin'), $result->authors);
    }

    public function testFindOneReturnsFirstOfMultipleResult()
    {
        $result = $this->selector->findOne('books', 'authors', 'Kevin');

        $this->assertInstanceOf('\StdClass', $result);

        $this->assertEquals('abc123', $result->isbn);
        $this->assertEquals('Abc', $result->title);
        $this->assertEquals(array('John', 'Kevin'), $result->authors);
    }

    public function testFindOneReturnsNullIfNotFound()
    {
        $result = $this->selector->findOne('books', 'title', '404');

        $this->assertNull($result);
    }

    public function testFindAllReturnsMultipleResult()
    {
        $result = $this->selector->findAll('books', 'authors', 'Kevin');

        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);

        // first book
        $this->assertEquals('abc123', $result[0]->isbn);
        $this->assertEquals('Abc', $result[0]->title);
        $this->assertEquals(array('John', 'Kevin'), $result[0]->authors);

        // second book
        $this->assertEquals('def456', $result[1]->isbn);
        $this->assertEquals('Def', $result[1]->title);
        $this->assertEquals(array('Kevin'), $result[1]->authors);
    }

    public function testFindAllReturnsEmptyArrayIfNotFound()
    {
        $result = $this->selector->findAll('books', 'authors', 'Pitagoras');

        $this->assertTrue(is_array($result));
        $this->assertCount(0, $result);
    }
}
