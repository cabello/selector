<?php

require 'src/Snit/Selector.php';
use Snit\Selector;

class SelectorTest extends PHPUnit_Framework_TestCase {
    function getDataInJsonString() {
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

    function getDataInStdClass() {
        return json_decode($this->getDataInJsonString());
    }

    function getDataInArray() {
        return json_decode($this->getDataInJsonString(), true);
    }

    function test_get_dictionary_for_documentation() {
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

    function test_get_list_for_documentation() {
        $data = $this->getDataInStdClass();
        $selector = new Selector($data);

        $expected = array(1,3,2);
        $result = $selector('[ school.staff.teachers.id ]');
        $this->assertEquals($expected, $result);

    }

    function test_get_attribute_simple_happy_path(){
        $jsonStub = new StdClass();
        $jsonStub->name = 'Willian';

        $bs = new Selector($jsonStub);

        $this->assertEquals(array('Willian'), $bs->getAll('name'));
        $this->assertEquals('Willian', $bs->getOne('name'));
    }

    function test_get_inexistent_attribute_should_return_null(){
        $jsonStub = new StdClass();

        $bs = new Selector($jsonStub);

        $this->assertEquals(array(), $bs->getAll('name' ));
        $this->assertEquals('', $bs->getOne('name' ));
    }

    function test_get_null_attribute_with_default_should_return_default(){
        $defaultStub = "default stub";
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = null;

        $bs = new Selector($jsonStub);
        $all = $bs->getAll('person.name', $defaultStub );
        $one = $bs->getOne('person.name', $defaultStub );

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    function test_get_inexistent_attribute_with_default_should_return_default(){
        $jsonStub = new StdClass();
        $defaultStub = "default stub";

        $bs = new Selector($jsonStub);
        $all = $bs->getAll('name', $defaultStub );
        $one = $bs->getOne('name', $defaultStub );

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    function test_get_attribute_from_null_data_should_return_null(){
        $jsonStub = null;

        $bs = new Selector($jsonStub);
        $all = $bs->getAll('name' );
        $one = $bs->getOne('name' );

        $this->assertEquals(array(), $all);
        $this->assertEquals('', $one);
    }

    function test_get_attribute_from_null_data_with_default_should_return_default(){
        $jsonStub = null;
        $defaultStub = "default stub";

        $bs = new Selector($jsonStub);
        $all = $bs->getAll('name', $defaultStub );
        $one = $bs->getOne('name', $defaultStub );

        $this->assertEquals($defaultStub, $all);
        $this->assertEquals($defaultStub, $one);
    }

    function test_get_nested_attribute_happy_path(){
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = 'Willian';
        $jsonStub->person->car = new stdClass();
        $jsonStub->person->car->color = 'red';

        $bs = new Selector($jsonStub);
        $name = $bs->getOne('person.name' );
        $carColor = $bs->getOne('person.car.color' );

        $this->assertEquals('Willian', $name);
        $this->assertEquals('red', $carColor);
    }

    function test_get_nested_invalid_attribute_should_return_null(){
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = 'Willian';

        $bs = new Selector($jsonStub);
        $carColor = $bs->getOne('person.car.color' );
        $allCarColors = $bs->getAll('person.car.color' );

        $this->assertEquals(null, $carColor);
        $this->assertEquals(array(), $allCarColors);
    }

    function test_get_nested_invalid_attribute_with_default_should_return_default(){
        $defaultStub = "default stub";

        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->name = 'Willian';

        $bs = new Selector($jsonStub);
        $carColor = $bs->getOne('person.car.color', $defaultStub );
        $allCarColors = $bs->getAll('person.car.color', $defaultStub );

        $this->assertEquals($defaultStub, $carColor);
        $this->assertEquals($defaultStub, $allCarColors);
    }

    function test_get_nested_attribute_collection_happy_path(){
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person->car = array( new stdClass, new stdClass );
        $jsonStub->person->car[0]->color = 'red';
        $jsonStub->person->car[1]->color = 'yellow';

        $bs = new Selector($jsonStub);
        $car_color = $bs->getOne('person.car.color');
        $car_colors = $bs->getAll('person.car.color');

        $this->assertEquals('red', $car_color);
        $this->assertEquals( array('red','yellow'), $car_colors);
    }

    function test_get_attribute_collection_with_nested_object_happy_path(){
        $jsonStub = new StdClass();
        $jsonStub->person = new StdClass();
        $jsonStub->person = array(new stdClass, new stdClass);
        $jsonStub->person[0]->car = new StdClass;
        $jsonStub->person[1]->car = new StdClass;
        $jsonStub->person[0]->car->color = 'red';
        $jsonStub->person[1]->car->color = 'yellow';

        $bs = new Selector($jsonStub);
        $car_color = $bs->getOne('person.car.color' );
        $car_colors = $bs->getAll('person.car.color' );

        $expected_all = array('red','yellow');
        $expected_one = 'red';

        $this->assertEquals( $expected_all, $car_colors);
        $this->assertEquals( $expected_one, $car_color);
    }


    function test_get_attribute_collection_with_invalid_nested_path_should_return_null(){
        $jsonStub = new StdClass();
        $jsonStub->person = array();

        $bs = new Selector($jsonStub);
        $car_color = $bs->getOne('person.car.color' );
        $car_colors = $bs->getAll('person.car.color' );

        $this->assertEquals( null, $car_color);
        $this->assertEquals( array(), $car_colors);
    }

    function test_get_attribute_collection_with_invalid_nested_path_with_default_should_return_default(){
        $defaultStub = "default stub";

        $jsonStub = new StdClass();
        $jsonStub->person = array();

        $bs = new Selector($jsonStub);
        $car_color = $bs->getOne('person.car.color', $defaultStub );
        $car_colors = $bs->getAll('person.car.color', $defaultStub );

        $this->assertEquals( $defaultStub, $car_color);
        $this->assertEquals( $defaultStub, $car_colors);
    }

    /** @dataProvider provideDataForGetDictionary
    */
    function test_getDictionary( $json, $expected ){
        $data = json_decode( $json );

        $parser = new Selector( $data );
        $dictionary = $parser->getDictionary( 'some.keys', 'some.values' );

        $this->assertEquals( $expected, $dictionary );
    }

    function provideDataForGetDictionary(){
        return array(
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : [ 1, 2 ] } }',
                array( 'a' => 1, 'b' => 2 )
            ),
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : [ 1, 2, 3 ] } }',
                array( 'a' => 1, 'b' => 2 )
            ),
            array(
                '{ "some" : { "keys" : null, "values" : [ 1, 2 ] } }',
                array()
            ),
            array(
                '{ "some" : { "keys" : [ "a", "b"], "values" : null } }',
                array( 'a' => null, 'b' => null )
            ),
        );
    }

    function test_constructing_with_json_string_should_convert_to_object() {
        $json = '{"key1":"foo", "key2":"bar"}';
        $parser = new Selector($json);
        $this->assertEquals("foo", $parser->getOne("key1"));
        $this->assertEquals("bar", $parser->getOne("key2"));
    }

    function test_find_should_return_correct_context() {
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
        $parser = new Selector( $json );

        //find one
        $result = $parser->findOne('staff.people', 'name', 'watinha2004');
        $this->assertTrue(is_object( $result ));
        $this->assertEquals(3, $result->id);
        $this->assertEquals('watinha2004', $result->name);

        //find all
        $result = $parser->findAll('staff.people', 'children.name', 'Alex');
        $this->assertTrue(is_array( $result ));
        $this->assertEquals(6, $result[0]->children[0]->id);
        $this->assertEquals('Alex', $result[0]->children[0]->name);

        //find one
        $result = $parser->findOne('staff.people.children', 'name', 'Homonimo');
        $this->assertTrue(is_object( $result ));
        $this->assertEquals(4, $result->id);
        $this->assertEquals('Homonimo', $result->name);

        //find all
        $result = $parser->findAll('staff.people.children', 'name', 'Homonimo');
        $this->assertTrue(is_array( $result ));
        $this->assertEquals(4, $result[0]->id);
        $this->assertEquals('Homonimo', $result[0]->name);
        $this->assertEquals(5, $result[1]->id);
        $this->assertEquals('Homonimo', $result[1]->name);
    }

    function test_supports_simple_array() {
        $array = array('display_name' => 'John Selector', 'age' => '34');
        $parser = new Selector($array);

        $result = $parser->getOne('display_name');
        $this->assertEquals('John Selector', $result);
    }

    function test_supports_complex_array() {
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

        $result = $parser->getOne('staff.people.name');
        $this->assertEquals('Luiz Honda', $result);
    }

    function test_supports_more_than_one_path() {
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

        $result = $parser->getOne('  profile.nickname.v | profile.nickname  ');
        $this->assertEquals('Luiz Honda', $result);

        $result = $parser->getOne('profile.gender.v');
        $this->assertEmpty($result);

        $result = $parser->getOne('profile.gender.v|profile.gender');
        $this->assertEquals('M', $result);
    }

    function test_should_be_callable_and_awesome() {
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
    function test_getDictionary_using_callable_form($json, $expected){
        $data = json_decode($json);

        $parser = new Selector($data);
        $dictionary = $parser(' { some.keys : some.values } ');

        $this->assertEquals($expected, $dictionary);
    }

    function test_focus_generates_a_new_parser() {
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

    function test_focus_stay_quiet_on_nonexistent_context() {
        $parser = new Selector();
        $focusedParser = $parser->focus('record.ydht.fields');

        $default = 'Unnamed';
        $this->assertEquals($default, $focusedParser('name.value', $default));
    }
}
