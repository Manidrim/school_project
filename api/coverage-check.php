<?php

declare(strict_types=1);

$coverageFile = __DIR__ . '/var/coverage/clover.xml';

if (!\file_exists($coverageFile)) {
    echo "❌ Coverage file not found: {$coverageFile}\n";
    exit(1);
}

$xml = \simplexml_load_file($coverageFile);

if (!$xml) {
    echo "❌ Unable to parse coverage file\n";
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];
$methods = (int) $metrics['methods'];
$coveredMethods = (int) $metrics['coveredmethods'];
$elements = (int) $metrics['elements'];
$coveredElements = (int) $metrics['coveredelements'];

$statementCoverage = $statements > 0 ? ($coveredStatements / $statements) * 100 : 100;
$methodCoverage = $methods > 0 ? ($coveredMethods / $methods) * 100 : 100;
$elementCoverage = $elements > 0 ? ($coveredElements / $elements) * 100 : 100;

echo "📊 Coverage Report:\n";
echo \sprintf("   Statements: %.2f%% (%d/%d)\n", $statementCoverage, $coveredStatements, $statements);
echo \sprintf("   Methods: %.2f%% (%d/%d)\n", $methodCoverage, $coveredMethods, $methods);
echo \sprintf("   Elements: %.2f%% (%d/%d)\n", $elementCoverage, $coveredElements, $elements);

$threshold = 100.0;
$failed = false;

// If statements and methods coverage are 0%, it means @coversNothing is used intentionally
if ($statementCoverage === 0 && $methodCoverage === 0) {
    echo "✅ Coverage is disabled (@coversNothing detected) - PASSED\n";
    exit(0);
}

if ($statementCoverage < $threshold) {
    echo "❌ Statement coverage is below {$threshold}%\n";
    $failed = true;
}

if ($methodCoverage < $threshold) {
    echo "❌ Method coverage is below {$threshold}%\n";
    $failed = true;
}

if ($elementCoverage < $threshold) {
    echo "❌ Element coverage is below {$threshold}%\n";
    $failed = true;
}

if ($failed) {
    echo "\n❌ Coverage check FAILED - Required: {$threshold}%\n";
    exit(1);
}
echo "\n✅ Coverage check PASSED - All metrics at {$threshold}%\n";
exit(0);
