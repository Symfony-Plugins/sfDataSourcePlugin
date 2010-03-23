<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$_test_dir = realpath(dirname(__FILE__).'/..');

if (!isset($_SERVER['SYMFONY']))
{
  //throw new RuntimeException('Could not find symfony core libraries.');
//  $_SERVER['SYMFONY'] = $_test_dir.'/../../../lib/symfony';
  $_SERVER['SYMFONY'] = $_test_dir.'/../../../../sf1.4';
}

require_once $_SERVER['SYMFONY'] . '/test/bootstrap/unit.php';
require_once $_SERVER['SYMFONY'] . '/lib/autoload/sfSimpleAutoload.class.php';


$autoload = sfSimpleAutoload::getInstance(sys_get_temp_dir().DIRECTORY_SEPARATOR.sprintf('sf_autoload_unit_propel_%s.data', md5(__FILE__)));
$autoload->addDirectory(realpath(dirname(__FILE__).'/../../lib'));
$autoload->register();

$_test_dir = realpath(dirname(__FILE__).'/..');
