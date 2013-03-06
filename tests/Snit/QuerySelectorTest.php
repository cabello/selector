<?php

use Snit\QuerySelector;

class QuerySelectorTest extends PHPUnit_Framework_TestCase
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
        $selector = new QuerySelector($data);

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
        $selector = new QuerySelector($data);

        $expected = array(1,3,2);
        $result = $selector('[ school.staff.teachers.id ]');
        $this->assertEquals($expected, $result);
    }

    public function test_get_one_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new QuerySelector($data);

        $expected = 'Luiz Honda';
        $result = $selector('school.staff.teachers.name');
        $this->assertEquals($expected, $result);
    }

    public function test_use_default_when_not_found_for_documentation()
    {
        $data = $this->getDataInStdClass();
        $selector = new QuerySelector($data);

        $expected = 21;
        $result = $selector('school.staff.teachers.age', 21);
        $this->assertEquals($expected, $result);
    }

    public function test_get_attribute_simple_happy_path()
    {
        $jsonStub = new StdClass();
        $jsonStub->name = 'Willian';

        $bs = new QuerySelector($jsonStub);

        $this->assertEquals(array('Willian'), $bs('[name]'));
        $this->assertEquals('Willian', $bs('name'));
    }

    public function test_get_inexistent_attribute_should_return_null()
    {
        $jsonStub = new StdClass();

        $bs = new QuerySelector($jsonStub);

        $this->assertEquals(array(), $bs('[name]'));
        $this->assertEquals('', $bs('name'));
    }

    public function test_get_null_attribute_with_default_should_return_default()
    {
        $defaultStub = "default stub";
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = null;

        $bs = new QuerySelector($jsonStub);
        $all = $bs('[person.name]', $defaultStub);
        $one = $bs('person.name', $defaultStub);

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    public function test_get_inexistent_attribute_with_default_should_return_default()
    {
        $jsonStub = new StdClass();
        $defaultStub = "default stub";

        $bs = new QuerySelector($jsonStub);
        $all = $bs('[name]', $defaultStub);
        $one = $bs('name', $defaultStub);

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    public function test_get_attribute_from_null_data_should_return_null()
    {
        $jsonStub = null;

        $bs = new QuerySelector($jsonStub);
        $all = $bs('[name]');
        $one = $bs('name');

        $this->assertEquals(array(), $all);
        $this->assertEquals('', $one);
    }

    public function test_get_attribute_from_null_data_with_default_should_return_default()
    {
        $jsonStub = null;
        $defaultStub = "default stub";

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $bs = new QuerySelector($jsonStub);
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

        $parser = new QuerySelector($data);
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
        $parser = new QuerySelector($json);
        $this->assertEquals("foo", $parser("key1"));
        $this->assertEquals("bar", $parser("key2"));
    }

    public function test_supports_simple_array()
    {
        $array = array('display_name' => 'John Selector', 'age' => '34');
        $parser = new QuerySelector($array);

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
        $parser = new QuerySelector($array);

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

        $parser = new QuerySelector($array);

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

        $parser = new QuerySelector($array);

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

        $parser = new QuerySelector($data);
        $dictionary = $parser(' { some.keys : some.values } ');

        $this->assertEquals($expected, $dictionary);
    }
}
