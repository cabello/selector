Selector [![Build Status](https://secure.travis-ci.org/cabello/selector.png)](http://travis-ci.org/cabello/selector)
=========

Problem
-------

Imagine that you have a `StdClass` instance full of attributes you want to retrieve. So you do something like this:

    $username = $photo->owner->username;

What happens if *username* is undefined and/or *owner*?

    $username = isset($photo->owner) && isset($photo->owner->username)
              ? $photo->owner->username
              : 'anonymous';

Imagine this kind of logic spread all over your codebase. What a mess!

Solution
--------

Selector turns the horrible code above into this:

    $photoSelector = Selector($photo);
    $username = $photoSelector('owner.username', 'anonymous');

You never have to worry again about checking if the `StdClass` have the properties you need, and as a plus you receive several ways of retrieving the info you need.

Features
--------

### Get attribute

    $default = 'not found';
    $attribute = $selector('foo.bar.baz', $default);

Is the same as `foo->bar->baz` but it checks if everything is set before going deep into the tree otherwise we would have an exception.

### Or

    $nameOrLastName = $selector('profile.name | profile.lastname')

### List

    $names = $selector('[ profile.name ]')

### Hash

    $nameAge = $selector('{ profile.name : profile.age }')
