Selector [![Build Status](https://secure.travis-ci.org/cabello/selector.png)](http://travis-ci.org/cabello/selector)
=========

Problem
-------

Imagine that you have a `StdClass` (possibly created by a `json_decode`) instance full of attributes you want to retrieve. So you do something like this:

    $username = $photo->owner->username;

What happens if *username* is undefined and/or *owner*?

    $username = isset($photo) && isset($photo->owner) && isset($photo->owner->username)
              ? $photo->owner->username
              : 'anonymous';

Imagine this kind of logic spread all over your codebase. What a mess!

Solution
--------

Selector turns the horrible code above into this:

    $photoSelector = Selector($photo);
    $username = $photoSelector->getOne('owner.username', 'anonymous');

You never have to worry again about checking if the `StdClass` have the properties you need, and as a plus you receive several ways of retrieving the info you need.

Features
--------

Will be revisited soon.

Developer
---------

Assuming you have `composer.phar` installed, it's simple to contribute to `selector`, clone the repository and run:

    cd selector # your clone folder
    composer.phar install --dev
    vendor/bin/phpunit tests

And you are ready to make new tests and contributions. :octocat:
