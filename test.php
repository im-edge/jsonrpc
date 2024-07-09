<?php

include __DIR__ . '/vendor/autoload.php';

echo "1\n";
$deferred = new \Amp\DeferredFuture();
$future = $deferred->getFuture();
echo "2\n";

$async = \Amp\async(function (\Amp\DeferredFuture $deferred) {
    echo "Async called\n";
    \Amp\delay(2);
    if ($deferred->isComplete()) {
        echo "- Already completed\n";
    } else {
        echo "Completing\n";
        $deferred->complete('Jo');
    }
}, $deferred);

echo "Async sent1\n";
echo "Async sent2\n";
echo "Async sent3\n";
//\Amp\delay(0.1);
echo "Async sent4\n";
echo "Async sent5\n";

// echo "Sending error\n";
$deferred->error(new Exception('nono'));
// echo "Sent error\n";
var_dump(\Amp\Future\await(['aa' => $future]));
echo "Waited\n";
