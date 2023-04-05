<?php

declare(strict_types=1);

/*
 * UserFrosting Core Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-core
 * @copyright Copyright (c) 2021 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-core/blob/master/LICENSE.md (MIT License)
 */

return [
    'required'      => 'harus diisi',
    'equals'        => "harus sama dengan '%s'",
    'different'     => "harus berbeda dengan '%s'",
    'accepted'      => 'harus diterima (accepted)',
    'numeric'       => 'harus berupa nomor/angka',
    'integer'       => 'harus berupa nilai integer (0-9)',
    'length'        => 'harus lebih panjang dari %d',
    'min'           => 'harus lebih besar dari %s',
    'max'           => 'harus kurang dari %s',
    'in'            => 'berisi nilai/value yang tidak valid',
    'notIn'         => 'berisi nilai/value yang tidak valid',
    'ip'            => 'format alamat IP tidak benar',
    'email'         => 'format alamat email tidak benar',
    'url'           => 'bukan format URL yang benar',
    'urlActive'     => 'harus berupa domain aktif',
    'alpha'         => 'hanya boleh menggunakan huruf a-z',
    'alphaNum'      => 'hanya boleh menggunakan huruf a-z dan atau nomor 0-9',
    'slug'          => 'hanya boleh menggunakan huruf a-z, nomor 0-9, tanda minus (-), dan uderscore atau strip bawah (_)',
    'regex'         => 'berisi karakter yang tidak valid',
    'date'          => 'format tanggal tidak valid',
    'dateFormat'    => "harus berupa tanggal dengan format '%s'",
    'dateBefore'    => "tanggal harus sebelum tanggal '%s'",
    'dateAfter'     => "tanggal harus sesudah tanggal '%s'",
    'contains'      => 'harus berisi %s',
    'boolean'       => 'harus berupa nilai boolean',
    'lengthBetween' => 'harus diantara karakter %d dan %d',
    'creditCard'    => 'nomor kartu kredit harus valid',
    'lengthMin'     => 'minimal berisi %d karakter',
    'lengthMax'     => 'maksimal berisi %d karakter',
];
