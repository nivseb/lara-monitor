<?php

namespace Tests\Datasets;

dataset(
    'simple method and path combinations',
    [
        'get request and fixed path'             => ['GET', '/test/route', 'GET /test/route'],
        'get request and path with parameter'    => ['GET', '/test/route/{myParam}', 'GET /test/route/{myParam}'],
        'put request and fixed path'             => ['PUT', '/test/route', 'PUT /test/route'],
        'put request and path with parameter'    => ['PUT', '/test/route/{myParam}', 'PUT /test/route/{myParam}'],
        'post request and fixed path'            => ['POST', '/test/route', 'POST /test/route'],
        'post request and path with parameter'   => ['POST', '/test/route/{myParam}', 'POST /test/route/{myParam}'],
        'delete request and fixed path'          => ['DELETE', '/test/route', 'DELETE /test/route'],
        'delete request and path with parameter' => ['DELETE', '/test/route/{myParam}', 'DELETE /test/route/{myParam}'],
        'option request and fixed path'          => ['OPTION', '/test/route', 'OPTION /test/route'],
        'option request and path with parameter' => ['OPTION', '/test/route/{myParam}', 'OPTION /test/route/{myParam}'],
        'head request and fixed path'            => ['HEAD', '/test/route', 'HEAD /test/route'],
        'head request and path with parameter'   => ['HEAD', '/test/route/{myParam}', 'HEAD /test/route/{myParam}'],
    ]
);

dataset(
    'possible redis commands',
    [
        'empty query' => ['', [], 'Unknown'],
        'only eval'   => ['eval', [], 'eval'],
        'eval SET'    => ['eval', ['SET'], 'SET'],
        'eval empty'  => ['eval', [''], 'Unknown'],
        'SET'         => ['SET', ['1234565'], 'SET'],
    ]
);

dataset(
    'possible sql queries',
    [
        'empty query'            => ['', 'Unknown', []],
        'simple select query'    => ['SELECT * FROM exampleTable', 'SELECT', ['exampleTable']],
        'select query with join' => [
            'SELECT * FROM exampleTable1 JOIN exampleTable2 ON exampleTable2.id = exampleTable1.id',
            'SELECT',
            ['exampleTable1', 'exampleTable2'],
        ],
        'insert query'      => ['INSERT INTO exampleTable VALUES (?,?)', 'INSERT', ['exampleTable']],
        'update query'      => ['UPDATE exampleTable SET name = ?', 'UPDATE', ['exampleTable']],
        'delete query'      => ['DELETE FROM exampleTable', 'DELETE', ['exampleTable']],
        'create table'      => ['CREATE TABLE exampleTable (id int)', 'CREATE TABLE', []],
        'drop  table'       => ['DROP TABLE exampleTable', 'DROP TABLE', []],
        'alter table'       => ['ALTER TABLE exampleTable ADD title varchar', 'ALTER TABLE', []],
        'execute procedure' => ['EXEC myExampleProcedure', 'Unknown', []],
    ]
);
