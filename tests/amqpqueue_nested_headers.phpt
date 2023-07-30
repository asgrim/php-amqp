--TEST--
AMQPQueue::get headers
--SKIPIF--
<?php if (!extension_loaded("amqp")) print "skip"; ?>
--FILE--
<?php
$cnn = new AMQPConnection();
$cnn->connect();

$ch = new AMQPChannel($cnn);

// create an error exchange and bind a queue to it:
$errorXchange = new AMQPExchange($ch);
$errorXchange->setName('errorXchange' . bin2hex(random_bytes(32)));
$errorXchange->setType(AMQP_EX_TYPE_TOPIC);
$errorXchange->declareExchange();

$errorQ = new AMQPQueue($ch);
$errorQ->setName('errorQueue' . bin2hex(random_bytes(32)));
$errorQ->declareQueue();
$errorQ->bind($errorXchange->getName(), '#');


// Declare a new exchange and queue using this dead-letter-exchange:
$ex = new AMQPExchange($ch);
$ex->setName('exchange-' . bin2hex(random_bytes(32)));
$ex->setType(AMQP_EX_TYPE_TOPIC);
$ex->declareExchange();
$q = new AMQPQueue($ch);
$q->setName('queue-' . bin2hex(random_bytes(32)));
$q->setArgument('x-dead-letter-exchange', $errorXchange->getName());
$q->declareQueue();
$q->bind($ex->getName(), '#');


// publish a message:
$ex->publish(
    'body', 'routing.1', AMQP_NOPARAM, array(
        'headers' => array(
            'foo' => 'bar',
            'baz' => array('a', 'bc', 'def')
        )
    )
);

// Read from the queue and reject, so it gets dead-lettered:
$msg = $q->get(AMQP_NOPARAM);
$q->nack($msg->getDeliveryTag());

sleep(1);
// Now read from the error queue:
$msg = $errorQ->get(AMQP_AUTOACK);

$header = $msg->getHeader('x-death');

echo isset($header[0]['count']) ? 'with' : 'without', ' count ', PHP_EOL;

unset($header[0]['count']);

var_dump($header);

$ex->delete();
$q->delete();
$errorXchange->delete();
$errorQ->delete();
?>
--EXPECTF--
%s
array(1) {
  [0]=>
  array(5) {
    ["reason"]=>
    string(8) "rejected"
    ["queue"]=>
    string(%d) "queue-%s"
    ["time"]=>
    object(AMQPTimestamp)#%d (1) {
      ["timestamp":"AMQPTimestamp":private]=>
      float(%d)
    }
    ["exchange"]=>
    string(%d) "exchange-%s"
    ["routing-keys"]=>
    array(1) {
      [0]=>
      string(9) "routing.1"
    }
  }
}