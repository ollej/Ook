Ook
===

A scaffolding tool to help setup new Nooku projects.

Examples
--------

Generate a component skeleton (with a controller and a view named foo):
    php ook.php -c generate -p Foo -m foo -f cv -a

 * Possible options in the second argument is vcmrth (view, controller, model, row, table, helper)

Create a model:
    php ook.php -c model -p Foo -m bar -b hittable,lockable,creatable -f subject,body:t,rating:i

Credits
-------

The code scaffolding is based on the generate.php script by Nils from the Nooku mailing list.

    http://snipt.net/theDigger/generate-nooku-packages

Nooku
-----

Read more about Nooku Framework here: http://www.nooku.org


