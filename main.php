<?php
/*
 * Copyright 2018 Daniel A. Hawton <daniel@hawton.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

require_once("vendor/autoload.php");
require_once("config.php");
require_once("default.config.php");

function main() {
    global $defaultconfig, $config;

    $config = array_merge($defaultconfig, $config);

    // Check config
    if ($config['aws']['bucket'] == "" || $config['aws']['access_key'] == '' || $config['aws']['secret'] == '') {
        echo "Config not setup properly for AWS. Ensure all fields are filled in.\n";
        exit;
    }
    if (!in_array($config['compression']['type'], ['gz','gzip','bz2','bzip2','xz'])) {
        echo "Invalid compression type defined. Supported types: gz/gzip, bz2/bzip2, xz\n";
        exit;
    }

    if (isset($config['prerun'])) {
        foreach($config['preruns'] as $cmd) {
            system ($cmd);
        }
    }

    $datestamp = date("Ymd");

    foreach($config['backups'] as $archive => $files) {
        $filename = "$datestamp.$archive"; // ext added by functions
        echo "Building archive $archive...\n";
        $archive = build_archive($filename, $files);
        echo "Archive $archive built. Starting upload script.\n";
        system("php upload.php $archive");
        echo "Upload script started.\n";
    }
    echo "Done.";
}


function build_archive($archive, $list) {
    if (is_array($list)) {
        $list = implode(" ", $list);
    }
    $tar = build_tar($archive, $list);
    $archive = compress($tar);
    return $archive;
}

function build_tar($archive, $files) {
    system("tar -cf $archive.tar $files");
    return "$archive.tar";
}

function compress($tar) {
    global $config;

    if ($config['compression']['type'] == "bz2" || $config['compression']['type'] == "bzip2") {
        $archive = build_bz2($tar);
    }
    if ($config['compression']['type'] == "gz" || $config['compression']['type'] == "gzip") {
        $archive = build_gz($tar);
    }
    if ($config['compression']['type'] == "xz") {
        $archive = build_xz($tar);
    }

    return $archive;
}

function build_bz2($tar) {
    global $config;
    $cmd = "bzip2 ";
    if ($config['compression']['level'] >= 0 && $config['compression']['level'] <= 9) {
        $cmd .= "-" . $config['compression']['level'] . " ";
    }
    system("$cmd $tar");
    return "$tar.bz2";
}

function build_gz($tar) {
    global $config;
    $cmd = "gzip ";
    if ($config['compression']['level'] >= 0 && $config['compression']['level'] <= 9) {
        $cmd .= "-" . $config['compression']['level'] . " ";
    }
    system("$cmd $tar");
    return "$tar.gz";
}

function build_xz($tar) {
    global $config;
    $cmd = "xz ";
    if ($config['compression']['level'] >= 0 && $config['compression']['level'] <= 9) {
        $cmd .= "-" . $config['compression']['level'] . " ";
    }
    system("$cmd $tar");
    return "$tar.gz";
}

main();
