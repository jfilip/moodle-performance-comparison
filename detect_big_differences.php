<?php

/**
 * Script to detect big changes between runs.
 *
 * More useful when running it through CLI as
 * it can be easily used from CI servers to check
 * the exit code.
 */

ini_set('display_errors', true);
ini_set('error_reporting', E_ALL);

require_once(__DIR__ . '/webapp/classes/test_plan_runs.php');
require_once(__DIR__ . '/webapp/classes/report.php');

// Removing the script name.
array_shift($argv);

if (empty($argv)) {
    echo 'Error: You need to specify the runs filenames without their .php sufix.' . PHP_EOL;
    exit(1);
}

if (count($argv) == 1) {
    echo 'Error: You should specify, at least, two runs to compare.' . PHP_EOL;
    exit(1);
}

// The filename without .php.
$timestamps = $argv;

$report = new report();
if (!$report->parse_runs($timestamps)) {
    echo 'Error: The selected runs are not comparable.' . PHP_EOL;
    foreach ($report->get_errors() as $var => $error) {
        echo $var . ': ' . $error . PHP_EOL;
    }
    exit(1);
}

// Uses the thresholds specified in the .properties files.
if (!$report->calculate_big_differences()) {
    echo 'Error: No way to get the default thresholds...' . PHP_EOL;
    exit(1);
}
$branches = $report->get_big_differences();

// Report changes.
$exitcode = 0;
if ($branches) {
    foreach ($branches as $branchnames => $changes) {
        foreach ($changes as $state => $data) {
            foreach ($data as $var => $steps) {
                foreach ($steps as $stepname => $info) {
                    echo "$branchnames - $state: $var - $stepname -> $info" . PHP_EOL;
                }
            }
        }

        if (!empty($changes['increment'])) {
            $exitcode = 1;
        }
    }
}

exit($exitcode);
