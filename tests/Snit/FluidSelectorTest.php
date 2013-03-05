<?php

use Snit\FluidSelector;

class FluidSelectorTest extends PHPUnit_Framework_TestCase
{
    public function getProfileDataInJsonString()
    {
        $json = '{
            "profile": {
                "firstname": "Danilo",
                "lastname": "Cabello",
                "birthday": "1987/01/27",
                "networks": {
                    "github" : "cabello",
                    "twitter" : "dcabello"
                },
                "languages": [
                    "Javascript",
                    "Python",
                    "PHP"
                ]
            }
        }';

        return $json;
    }

    public function getProfileDataInStdClass()
    {
        return json_decode($this->getProfileDataInJsonString());
    }

    public function getBookDataInJsonString()
    {
        $json = '{
            "book": [
                {
                    "isbn": "abc123",
                    "title": "Abc",
                    "authors": ["John", "Kevin"],
                    "edition": null
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
        $this->selector = new FluidSelector($this->getBookDataInStdClass());
    }

    public function testFluidSelector()
    {
        $result = $this->selector->find('book.isbn')->limit(1)->fetch();

        $this->assertEquals('abc123', $result);

        $result = $this->selector->find('book.summary')->defaultTo('Summary to be written')->fetch();

        $this->assertEquals('Summary to be written', $result);
    }

    public function testssss()
    {
        $selector = new FluidSelector($this->getProfileDataInStdClass());

        $this->assertEquals('Danilo', $selector->find('profile.firstname')->fetch());

        $this->assertEquals('', $selector->find('profile.initials')->fetch());

        $this->assertEquals('', $selector->find('profile.name')->fetch());
        $this->assertEquals('Danilo', $selector->find('account.name')->orFind('profile.firstname')->fetch());
        $this->assertEquals('Danilo', $selector->find('profile.firstname')->orFind('account.name')->fetch());

        $this->assertEquals('Anonymous', $selector->find('crazy.name')->orFind('warcraft.name')->defaultTo('Anonymous')->fetch());
    }
}
