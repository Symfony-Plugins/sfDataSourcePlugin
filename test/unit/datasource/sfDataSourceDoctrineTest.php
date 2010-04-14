<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

function iterator_to_field_array($iterator, $field)
{
  $values = array();
  foreach ($iterator as $key => $value)
  {
    $values[] = $value[$field];
  }
  return $values;
}

// initialize Doctrine
$autoload = sfSimpleAutoload::getInstance(sys_get_temp_dir().DIRECTORY_SEPARATOR.sprintf('sf_autoload_unit_doctrine_%s.data', md5(__FILE__)));
$autoload->addDirectory(realpath($_SERVER['SYMFONY'].'/plugins/sfDoctrinePlugin/lib'));
$autoload->register();

//class ProjectConfiguration extends sfProjectConfiguration {}
class ProjectConfiguration extends sfProjectConfiguration
{
//  protected $plugins = array('sfPropel15Plugin');
  
  public function setup()
  {
    $this->setPluginPath('sfDoctrinePlugin', dirname(__FILE__).'/../../../../sfPropel15Plugin');
  }
}

$configuration = new ProjectConfiguration(dirname(__FILE__).'/../../lib', new sfEventDispatcher());
$database = new sfDoctrineDatabase(array('name' => 'doctrine', 'dsn' => 'sqlite::memory:'));

Doctrine::createTablesFromModels(dirname(__FILE__).'/fixtures');

// initialize data
$coll = new Doctrine_Collection('Person');
$coll[]->name = 'Fabien';
$coll[]->name = 'Francois';
$coll[]->name = 'Jonathan';
$coll[]->name = 'Fabian';
$coll[]->name = 'Kris';
$coll[]->name = 'Nicolas';
$coll[]->name = 'Dustin';
$coll[]->name = 'Carl';
$coll->save();

$t = new lime_test(52, new lime_output_color());

// ->__construct()
$t->diag('->__construct()');

$s = new sfDataSourceDoctrine('Person');
$t->is($s->current()->id, 1, '->__construct() accepts a Doctrine record class name as argument');

$q = Doctrine_Query::create()->from('Person');
$s = new sfDataSourceDoctrine($q);
$t->is($s->current()->id, 1, '->__construct() accepts a Doctrine query as argument');

$s = new sfDataSourceDoctrine(Doctrine::getTable('Person')->findAll());
$t->is($s->current()->id, 1, '->__construct() accepts a Doctrine collection as argument');

try
{
  $s = new sfDataSourceDoctrine('foobar');
  $t->fail('->__construct() throws an "UnexpectedValueException" if the given class name is no Doctrine record');
}
catch (UnexpectedValueException $e)
{
  $t->pass('->__construct() throws an "UnexpectedValueException" if the given class name is no Doctrine record');
}

try
{
  $s = new sfDataSourceDoctrine(new stdClass);
  $t->fail('->__construct() throws an "InvalidArgumentException" if the argument is not valid');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->__construct() throws an "InvalidArgumentException" if the argument is not valid');
}

// ->current()
$t->diag('->current()');
$s = new sfDataSourceDoctrine('Person');

$t->isa_ok($s->current(), 'Person', '->current() returns the first result');
$t->is($s->current()->id, 1, '->current() returns the first result');
$s->next();
$t->is($s->current()->id, 2, '->current() returns the current result when iterating');

foreach ($s as $k => $v);

try
{
  $s->current();
  $t->fail('->current() throws an "OutOfBoundsException" when accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('->current() throws an "OutOfBoundsException" when accessed after iterating');
}

// SeekableIterator interface
$t->diag('SeekableIterator interface');
$s = new sfDataSourceDoctrine('Person');
$t->is(array_keys(iterator_to_array($s)), range(0, 7), 'sfDataSourceDoctrine implements the SeekableIterator interface');
$t->is(count(iterator_to_array($s)), 8, 'sfDataSourceDoctrine implements the SeekableIterator interface');

$s = new sfDataSourceDoctrine('Person');
$s->seek(1);
$t->is($s['id'], 2, 'sfDataSourceDoctrine implements the SeekableIterator interface');
$t->is($s['name'], 'Francois', 'sfDataSourceDoctrine implements the SeekableIterator interface');

try
{
  $s->seek(30);
  $t->fail('->seek() throws an "OutOfBoundsException" when the given index is too large');
}
catch (OutOfBoundsException $e)
{
  $t->pass('->seek() throws an "OutOfBoundsException" when the given index is too large');
}

try
{
  $s->seek(-1);
  $t->fail('->seek() throws an "OutOfBoundsException" when the given index is too small');
}
catch (OutOfBoundsException $e)
{
  $t->pass('->seek() throws an "OutOfBoundsException" when the given index is too small');
}

// Countable interface
$t->diag('Countable interface');
$s = new sfDataSourceDoctrine('Person');
$t->is(count($s), 8, 'sfDataSourceDoctrine implements the Countable interface');
$s->setLimit(4);
$t->is(count($s), 4, 'sfDataSourceDoctrine implements the Countable interface');
$s->setOffset(5);
$t->is(count($s), 3, 'sfDataSourceDoctrine implements the Countable interface');

// ->countAll()
$t->diag('->countAll()');
$s = new sfDataSourceDoctrine('Person');
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');
$s->setLimit(4);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');
$s->setOffset(5);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');

$s = new sfDataSourceDoctrine(Doctrine::getTable('Person')->findAll());
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');
$s->setLimit(4);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');
$s->setOffset(5);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');

// ->setLimit()
$t->diag('->setLimit()');
$s = new sfDataSourceDoctrine('Person');
$s->setLimit(4);
$t->is(iterator_to_field_array($s, 'id'), range(1,4), '->setLimit() limits the records returned by the iterator');

$coll = Doctrine::getTable('Person')->findAll();
$s = new sfDataSourceDoctrine($coll);
$s->setLimit(4);
$t->is(iterator_to_field_array($s, 'id'), range(1,4), '->setLimit() limits the records returned by the iterator');

// ->setOffset()
$t->diag('->setOffset()');
$s = new sfDataSourceDoctrine('Person');
$s->setOffset(3);
$t->is(iterator_to_field_array($s, 'id'), range(4,8), '->setOffset() sets the offset of the iterator');

$s->setOffset(30);
$t->is(iterator_to_field_array($s, 'id'), array(), '->setOffset() sets the offset of the iterator');

$s->setOffset(2);
$s->seek(1);
$t->is($s['id'], 4, '->setOffset() sets the offset of the iterator');
$t->is($s['name'], 'Fabian', '->setOffset() sets the offset of the iterator');

$s = new sfDataSourceDoctrine(Doctrine::getTable('Person')->findAll());
$s->setOffset(3);
$t->is(iterator_to_field_array($s, 'id'), range(4,8), '->setOffset() sets the offset of the iterator');

// ArrayAccess interface
$t->diag('ArrayAccess interface');
$s = new sfDataSourceDoctrine('Person');

$t->is($s['id'], 1, 'sfDataSourceDoctrine implements the ArrayAccess interface');
$t->is($s['name'], 'Fabien', 'sfDataSourceDoctrine implements the ArrayAccess interface');
$t->ok(isset($s['id']), 'sfDataSourceDoctrine implements the ArrayAccess interface');
$t->ok(!isset($s['foobar']), 'sfDataSourceDoctrine implements the ArrayAccess interface');
$s->next();
$t->is($s['id'], 2, 'sfDataSourceDoctrine implements the ArrayAccess interface');
$t->is($s['name'], 'Francois', 'sfDataSourceDoctrine implements the ArrayAccess interface');

try
{
  $s['name'] = 'Foobar';
  $t->fail('sfDataSourceDoctrine throws a "LogicException" when fields are set using ArrayAccess');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourceDoctrine throws a "LogicException" when fields are set using ArrayAccess');
}
try
{
  unset($s['name']);
  $t->fail('sfDataSourceDoctrine throws a "LogicException" when fields are unset using ArrayAccess');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourceDoctrine throws a "LogicException" when fields are unset using ArrayAccess');
}

foreach ($s as $k => $v);

try
{
  $s['name'];
  $t->fail('sfDataSourceDoctrine throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourceDoctrine throws an "OutOfBoundsException" when fields are accessed after iterating');
}
try
{
  isset($s['name']);
  $t->fail('sfDataSourceDoctrine throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourceDoctrine throws an "OutOfBoundsException" when fields are accessed after iterating');
}

// ->getTable()
$t->diag('->getTable()');
$s = new sfDataSourceDoctrine('Person'); // string initialization
$t->isa_ok($s->getTable(), 'Doctrine_Table', '->getTable() returns the doctrine table of the data source');
$t->is($s->getTable()->getComponentName(), 'Person', '->getTable() returns the doctrine table of the data source');

$q = Doctrine_Query::create()->from('Person');
$s = new sfDataSourceDoctrine($q); // query initialization
$t->isa_ok($s->getTable(), 'Doctrine_Table', '->getTable() returns the doctrine table of the data source');
$t->is($s->getTable()->getComponentName(), 'Person', '->getTable() returns the doctrine table of the data source');

$s = new sfDataSourceDoctrine(Doctrine::getTable('Person')->findAll()); // collection initialization
$t->isa_ok($s->getTable(), 'Doctrine_Table', '->getTable() returns the doctrine table of the data source');
$t->is($s->getTable()->getComponentName(), 'Person', '->getTable() returns the doctrine table of the data source');

// ->setSort()
$t->diag('->setSort()');
$s = new sfDataSourceDoctrine('Person'); // string initialization
$originalValues = array();
$coll = Doctrine_Query::create()->from('Person')->execute();
foreach ($coll as $record)
{
  $originalValues[] = $record->name;
}

$s->setSort('name', sfDataSourceInterface::DESC);
rsort($originalValues);
$t->is(iterator_to_field_array($s, 'name'), $originalValues, '->setSort() sorts correctly');

$s->setSort('name', sfDataSourceInterface::ASC);
sort($originalValues);
$t->is(iterator_to_field_array($s, 'name'), $originalValues, '->setSort() sorts correctly');

$q = Doctrine_Query::create()->from('Person');
$s = new sfDataSourceDoctrine($q); // query initialization
$s->setSort('name', sfDataSourceInterface::DESC);
rsort($originalValues);
$t->is(iterator_to_field_array($s, 'name'), $originalValues, '->setSort() sorts correctly');

$s->setSort('name', sfDataSourceInterface::ASC);
sort($originalValues);
$t->is(iterator_to_field_array($s, 'name'), $originalValues, '->setSort() sorts correctly');

$s = new sfDataSourceDoctrine(Doctrine::getTable('Person')->findAll()); // collection initialization
try
{
  $s->setSort('name');
  $t->fail('->setSort() throws a "RuntimeException" when the source is based on a Doctrine_Collection');
}
catch (RuntimeException $e)
{
  $t->pass('->setSort() throws a "RuntimeException" when the source is based on a Doctrine_Collection');
}

