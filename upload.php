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

// Check config
if ($config['aws']['bucket'] == "" || $config['aws']['access_key'] == '' || $config['aws']['secret'] == '' || $config['aws']['acl'] == '') {
    echo "Config not setup properly for AWS. Ensure all fields are filled in.\n";
    exit;
}

$pid = pcntl_fork();
if ($pid == -1) {
    start_upload($argv); // Can't fork, upload as normal process
} else if ($pid) {
    // We've forked and are parent, end this process.
    exit;
} else {
    start_upload($argv);
}

function start_upload($argv) {
    global $config;
    $archive = $argv[1];
    $key = isset($argv[2]) ? $argv[2] : $argv[1];

    $s3 = Aws\S3\S3Client::factory(
        [
            'credentials' => [
                'key' => $config['aws']['access_key'],
                'secret' => $config['aws']['secret']
            ],
            'version' => 'latest',
            'region' => ($config['aws']['region'] != "") ? $config['aws']['region'] : 'us-west-2',
        ]
    );
    $s3->putObject([
        'Bucket' => $config['aws']['bucket'],
        'Key' => $key,
        'SourceFile' => $archive,
        'ACL' => $config['aws']['acl'],
        'StorageClass' => $config['aws']['storage_class']
    ]);
}
