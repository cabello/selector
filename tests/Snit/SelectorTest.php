<?php

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

    public function test_get_dictionary_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new Selector($data);

        $expected = array(
            '1' => 'Luiz Honda',
            '3' => 'Willian Watanabe',
            '2' => 'Rafael Martins'
       );
        $result = $selector('{ school.staff.teachers.id : school.staff.teachers.name }');
        $this->assertEquals($expected, $result);
    }

    public function test_get_list_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new Selector($data);

        $expected = array(1,3,2);
        $result = $selector('[ school.staff.teachers.id ]');
        $this->assertEquals($expected, $result);
    }

    public function test_get_one_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new Selector($data);

        $expected = 'Luiz Honda';
        $result = $selector('school.staff.teachers.name');
        $this->assertEquals($expected, $result);
    }

    public function test_use_default_when_not_found_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new Selector($data);

        $expected = 21;
        $result = $selector('school.staff.teachers.age', 21);
        $this->assertEquals($expected, $result);
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
        $result = $focusedSelector('{ id : name }');
        $this->assertEquals($expected, $result);
    }

    public function test_get_attribute_simple_happy_path()
    {
        $jsonStub = new StdClass();
        $jsonStub->name = 'Willian';

        $bs = new Selector($jsonStub);

        $this->assertEquals(array('Willian'), $bs('[name]'));
        $this->assertEquals('Willian', $bs('name'));
    }

    public function test_get_inexistent_attribute_should_return_null()
    {
        $jsonStub = new StdClass();

        $bs = new Selector($jsonStub);

        $this->assertEquals(array(), $bs('[name]'));
        $this->assertEquals('', $bs('name'));
    }

    public function test_get_null_attribute_with_default_should_return_default()
    {
        $defaultStub = "default stub";
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = null;

        $bs = new Selector($jsonStub);
        $all = $bs('[person.name]', $defaultStub);
        $one = $bs('person.name', $defaultStub);

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    public function test_get_inexistent_attribute_with_default_should_return_default()
    {
        $jsonStub = new StdClass();
        $defaultStub = "default stub";

        $bs = new Selector($jsonStub);
        $all = $bs('[name]', $defaultStub);
        $one = $bs('name', $defaultStub);

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    public function test_get_attribute_from_null_data_should_return_null()
    {
        $jsonStub = null;

        $bs = new Selector($jsonStub);
        $all = $bs('[name]');
        $one = $bs('name');

        $this->assertEquals(array(), $all);
        $this->assertEquals('', $one);
    }

    public function test_get_attribute_from_null_data_with_default_should_return_default()
    {
        $jsonStub = null;
        $defaultStub = "default stub";

        $bs = new Selector($jsonStub);
        $all = $bs('[name]', $defaultStub);
        $one = $bs('name', $defaultStub);

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    public function test_get_nested_attribute_happy_path()
    {
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = 'Willian';
        $jsonStub->person->car = new stdClass();
        $jsonStub->person->car->color = 'red';

        $bs = new Selector($jsonStub);
        $name = $bs('person.name');
        $carColor = $bs('person.car.color');

        $this->assertEquals('Willian', $name);
        $this->assertEquals('red', $carColor);
    }

    public function test_get_nested_invalid_attribute_should_return_null()
    {
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = 'Willian';

        $bs = new Selector($jsonStub);
        $carColor = $bs('person.car.color');
        $allCarColors = $bs('[person.car.color]');

        $this->assertEquals(null, $carColor);
        $this->assertEquals(array(), $allCarColors);
    }

    public function test_get_nested_invalid_attribute_with_default_should_return_default()
    {
        $defaultStub = "default stub";

        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = 'Willian';

        $bs = new Selector($jsonStub);
        $carColor = $bs('person.car.color', $defaultStub);
        $allCarColors = $bs('[person.car.color]', $defaultStub);

        $this->assertEquals($defaultStub, $carColor);
        $this->assertEquals($defaultStub, $allCarColors);
    }

    public function test_get_nested_attribute_collection_happy_path()
    {
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->car = array(new stdClass, new stdClass);
        $jsonStub->person->car[0]->color = 'red';
        $jsonStub->person->car[1]->color = 'yellow';

        $bs = new Selector($jsonStub);
        $car_color = $bs('person.car.color');
        $car_colors = $bs('[person.car.color]');

        $this->assertEquals('red', $car_color);
        $this->assertEquals(array('red','yellow'), $car_colors);
    }

    public function test_get_attribute_collection_with_nested_object_happy_path()
    {
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person = array(new stdClass, new stdClass);
        $jsonStub->person[0]->car = new StdClass;
        $jsonStub->person[1]->car = new StdClass;
        $jsonStub->person[0]->car->color = 'red';
        $jsonStub->person[1]->car->color = 'yellow';

        $bs = new Selector($jsonStub);
        $car_color = $bs('person.car.color');
        $car_colors = $bs('[person.car.color]');

        $expected_all = array('red','yellow');
        $expected_one = 'red';

        $this->assertEquals($expected_all, $car_colors);
        $this->assertEquals($expected_one, $car_color);
    }

    public function test_get_attribute_collection_with_invalid_nested_path_should_return_null()
    {
        $jsonStub = new StdClass();
        $jsonStub->person = array();

        $bs = new Selector($jsonStub);
        $car_color = $bs('person.car.color');
        $car_colors = $bs('[person.car.color]');

        $this->assertEquals(null, $car_color);
        $this->assertEquals(array(), $car_colors);
    }

    public function test_get_attribute_collection_with_invalid_nested_path_with_default_should_return_default()
    {
        $defaultStub = "default stub";

        $jsonStub = new StdClass();
        $jsonStub->person = array();

        $bs = new Selector($jsonStub);
        $car_color = $bs('person.car.color', $defaultStub);
        $car_colors = $bs('[person.car.color]', $defaultStub);

        $this->assertEquals($defaultStub, $car_color);
        $this->assertEquals($defaultStub, $car_colors);
    }

    /** @dataProvider provideDataForGetDictionary
    */
    public function test_getDictionary($json, $expected)
    {
        $data = json_decode($json);

        $parser = new Selector($data);
        $dictionary = $parser('{ some.keys : some.values }');

        $this->assertEquals($expected, $dictionary);
    }

    public function provideDataForGetDictionary()
    {
        return array(
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : [ 1, 2 ] } }',
                array('a' => 1, 'b' => 2)
            ),
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : [ 1, 2, 3 ] } }',
                array('a' => 1, 'b' => 2)
            ),
            array(
                '{ "some" : { "keys" : null, "values" : [ 1, 2 ] } }',
                array()
            ),
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : [ 1 ] } }',
                array('a' => 1, 'b' => null)
            ),
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : null } }',
                array('a' => null, 'b' => null)
            ),
       );
    }

    public function test_constructing_with_json_string_should_convert_to_object()
    {
        $json = '{"key1":"foo", "key2":"bar"}';
        $parser = new Selector($json);
        $this->assertEquals("foo", $parser("key1"));
        $this->assertEquals("bar", $parser("key2"));
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
    }

    public function test_supports_simple_array()
    {
        $array = array('display_name' => 'John Selector', 'age' => '34');
        $parser = new Selector($array);

        $result = $parser('display_name');
        $this->assertEquals('John Selector', $result);
    }

    public function test_supports_complex_array()
    {
        $array = array('staff' => array(
            'people' => array(
                array(
                    'id' => 1,
                    'name' => 'Luiz Honda',
                    'children' => array(
                        array('id' => 6, 'name' => 'Alex'),
                   ),
               ),
                array(
                    'id' => 3,
                    'name' => 'watinha2004',
                    'visibility' => 'private',
               ),
                array(
                    'id' => 2,
                    'name' => 'Danilo Cabello',
                    'children' => array(
                        array('id' => 4, 'name' => 'Homonimo'),
                        array('id' => 5, 'name' => 'Homonimo'),
                   ),
               ),
           ),
       ));
        $parser = new Selector($array);

        $result = $parser('staff.people.name');
        $this->assertEquals('Luiz Honda', $result);
    }

    public function test_supports_more_than_one_path()
    {
        $json = '{
            "profile" : {
                "nickname" : {
                    "v" : "Luiz Honda",
                    "p" : "PUBLIC"
                },
                "gender" : "M"
            }
        }';
        $array = json_decode($json);

        $parser = new Selector($array);

        $result = $parser('  profile.nickname.v | profile.nickname  ');
        $this->assertEquals('Luiz Honda', $result);

        $result = $parser('profile.gender.v');
        $this->assertEmpty($result);

        $result = $parser('profile.gender.v|profile.gender');
        $this->assertEquals('M', $result);
    }

    public function test_should_be_callable_and_awesome()
    {
        $json = '{
            "profile" : {
                "nickname" : ["Luiz Honda", "Rafael Martins", "Danilo Cabello"]
            }
        }';
        $array = json_decode($json);

        $parser = new Selector($array);

        // this way it will call getOne
        $result = $parser('profile.nickname');
        $this->assertEquals('Luiz Honda', $result);

        // this way it will call getAll
        $result = $parser('[profile.nickname]');
        $expected = array('Luiz Honda', 'Rafael Martins', 'Danilo Cabello');
        $this->assertEquals($expected, $result);
    }

    /** @dataProvider provideDataForGetDictionary
    */
    public function test_getDictionary_using_callable_form($json, $expected)
    {
        $data = json_decode($json);

        $parser = new Selector($data);
        $dictionary = $parser(' { some.keys : some.values } ');

        $this->assertEquals($expected, $dictionary);
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

        $this->assertEquals('Danilo', $focusedParser('name.value'));
    }

    public function test_focus_stay_quiet_on_nonexistent_context()
    {
        $parser = new Selector();
        $focusedParser = $parser->focus('record.ydht.fields');

        $default = 'Unnamed';
        $this->assertEquals($default, $focusedParser('name.value', $default));
    }
}
