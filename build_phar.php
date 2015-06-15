#!/usr/bin/env php
<?php
$phar = new Phar(dirname(__FILE__).'/phpresttest.phar');
$phar->buildFromDirectory(dirname(__FILE__).'/PHPRT');
$stub = $phar->createDefaultStub('index.php', 'index.php');
$stub = "#!/usr/bin/env php\n" . $stub;
$phar->setStub($stub);
