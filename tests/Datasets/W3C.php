<?php

namespace Tests\Datasets;

use Nivseb\LaraMonitor\Struct\Tracing\W3CTraceParent;

dataset(
    'sampled flags',
    [
        '01', '03', '05', '07', '09', '0b', '0d', '0f',
        '11', '13', '15', '17', '19', '1b', '1d', '1f',
        '21', '23', '25', '27', '29', '2b', '2d', '2f',
        '31', '33', '35', '37', '39', '3b', '3d', '3f',
        '41', '43', '45', '47', '49', '4b', '4d', '4f',
        '51', '53', '55', '57', '59', '5b', '5d', '5f',
        '61', '63', '65', '67', '69', '6b', '6d', '6f',
        '71', '73', '75', '77', '79', '7b', '7d', '7f',
        '81', '83', '85', '87', '89', '8b', '8d', '8f',
        '91', '93', '95', '97', '99', '9b', '9d', '9f',
        'a1', 'a3', 'a5', 'a7', 'a9', 'ab', 'ad', 'af',
        'b1', 'b3', 'b5', 'b7', 'b9', 'bb', 'bd', 'bf',
        'c1', 'c3', 'c5', 'c7', 'c9', 'cb', 'cd', 'cf',
        'd1', 'd3', 'd5', 'd7', 'd9', 'db', 'dd', 'df',
        'e1', 'e3', 'e5', 'e7', 'e9', 'eb', 'ed', 'ef',
        'f1', 'f3', 'f5', 'f7', 'f9', 'fb', 'fd', 'ff',
    ]
);

dataset(
    'not sampled flags',
    [
        '00', '02', '04', '06', '08', '0a', '0c', '0e',
        '10', '12', '14', '16', '18', '1a', '1c', '1e',
        '20', '22', '24', '26', '28', '2a', '2c', '2e',
        '30', '32', '34', '36', '38', '3a', '3c', '3e',
        '40', '42', '44', '46', '48', '4a', '4c', '4e',
        '50', '52', '54', '56', '58', '5a', '5c', '5e',
        '60', '62', '64', '66', '68', '6a', '6c', '6e',
        '70', '72', '74', '76', '78', '7a', '7c', '7e',
        '80', '82', '84', '86', '88', '8a', '8c', '8e',
        '90', '92', '94', '96', '98', '9a', '9c', '9e',
        'a0', 'a2', 'a4', 'a6', 'a8', 'aa', 'ac', 'ae',
        'b0', 'b2', 'b4', 'b6', 'b8', 'ba', 'bc', 'be',
        'c0', 'c2', 'c4', 'c6', 'c8', 'ca', 'cc', 'ce',
        'd0', 'd2', 'd4', 'd6', 'd8', 'da', 'dc', 'de',
        'e0', 'e2', 'e4', 'e6', 'e8', 'ea', 'ec', 'ee',
        'f0', 'f2', 'f4', 'f6', 'f8', 'fa', 'fc', 'fe',
    ]
);

dataset(
    'invalid trace header',
    [
        'bad prefix 01'        => '01-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01',
        'bad prefix 10'        => '10-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01',
        'bad prefix 11'        => '11-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01',
        'wrong trace length'   => '00-0af7651916cd43dd8448eb211c80319-b7ad6b7169203331-01',
        'wrong parent length'  => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b716920333-01',
        'bad trace character'  => '00-0af7651916cd43dd8448eb211c80319z-b7ad6b7169203331-01',
        'bad parent character' => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b716920333z-01',
    ]
);

dataset(
    'w3c parents',
    [
        'unsampled w3c trace header' => fn () => new W3CTraceParent(
            '00',
            bin2hex(random_bytes(16)),
            bin2hex(random_bytes(8)),
            '00'
        ),
        'sampled w3c trace header' => fn () => new W3CTraceParent(
            '00',
            bin2hex(random_bytes(16)),
            bin2hex(random_bytes(8)),
            '01'
        ),
    ]
);
