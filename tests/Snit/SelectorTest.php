<?php

use Snit\QuerySelector;
use Snit\Selector;

class SelectorTest extends PHPUnit_Framework_TestCase
{
    public function getDataInJsonString()
    {
        $json = '{
            "school": {
              "name": "Boston High School",
              "staff": {
                "teachers": [
                  {
                    "id": 1,
                    "name": "Luiz Honda",
                    "children": [
                      {
                        "id": 6,
                        "name": "Alex"
                      }
                    ]
                  },
                  {
                    "id": 3,
                    "name": "Willian Watanabe",
                    "visibility": "private"
                  },
                  {
                    "id": 2,
                    "name": "Rafael Martins",
                    "children": [
                      {
                        "id": 4,
                        "name": "Gabriel"
                      }
                    ]
                  }
                ]
              }
            }
        }';

        return $json;
    }

    public function getDataInStdClass()
    {
        return json_decode($this->getDataInJsonString());
    }

    public function test_focus_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new Selector($data);
        $focusedSelector = $selector->focus('school.staff.teachers');

        $expected = array(
            '1' => 'Luiz Honda',
            '3' => 'Willian Watanabe',
            '2' => 'Rafael Martins'
       );
        $result = $focusedSelector->getDictionaryFromPath('{id:name}');
        $this->assertEquals($expected, $result);
    }

    public function test_find_should_return_correct_context()
    {
        $json = '{
            "staff" : {
                "people" : [
                    {
                        "id" : 1,
                        "name" :  "Luiz Honda",
                        "children" : [
                            {
                                "id" : 6,
                                "name" :  "Alex"
                            }
                        ]
                    },
                    {
                        "id" : 3,
                        "name" :  "watinha2004",
                        "visibility" : "private"
                    },
                    {
                        "id" : 2,
                        "name" :  "Danilo Cabello",
                        "children" : [
                            {
                                "id" : 4,
                                "name" :  "Homonimo"
                            },
                            {
                                "id" : 5,
                                "name" :  "Homonimo"
                            }
                        ]
                    }
                ]
            }
        }';
        $parser = new Selector($json);

        //find one
        $result = $parser->findOne('staff.people', 'name', 'watinha2004');
        $this->assertTrue(is_object($result));
        $this->assertEquals(3, $result->id);
        $this->assertEquals('watinha2004', $result->name);

        //find all
        $result = $parser->findAll('staff.people', 'children.name', 'Alex');
        $this->assertTrue(is_array($result));
        $this->assertEquals(6, $result[0]->children[0]->id);
        $this->assertEquals('Alex', $result[0]->children[0]->name);

        //find one
        $result = $parser->findOne('staff.people.children', 'name', 'Homonimo');
        $this->assertTrue(is_object($result));
        $this->assertEquals(4, $result->id);
        $this->assertEquals('Homonimo', $result->name);

        //find all
        $result = $parser->findAll('staff.people.children', 'name', 'Homonimo');
        $this->assertTrue(is_array($result));
        $this->assertEquals(4, $result[0]->id);
        $this->assertEquals('Homonimo', $result[0]->name);
        $this->assertEquals(5, $result[1]->id);
        $this->assertEquals('Homonimo', $result[1]->name);

        //find one
        $result = $parser->findOne('staff.people', 'name', 'undefined');
        $this->assertFalse(is_object($result));
        $this->assertNull($result);
    }

    public function test_focus_generates_a_new_parser()
    {
        $json = '{
            "record" : {
                "ydht" : {
                    "fields" : {
                        "name" : { "value" : "Danilo" },
                        "age" : { "value" : 25 }
                    }
                }
            }
        }';
        $data = json_decode($json);

        $parser = new Selector($data);
        $focusedParser = $parser->focus('record.ydht.fields');

        $this->assertEquals('Danilo', $focusedParser->getOne('name.value'));
    }

    public function test_focus_stay_quiet_on_nonexistent_context()
    {
        $parser = new Selector();
        $focusedParser = $parser->focus('record.ydht.fields');

        $default = 'Unnamed';
        $this->assertEquals($default, $focusedParser->getOne('name.value', $default));
    }
}
