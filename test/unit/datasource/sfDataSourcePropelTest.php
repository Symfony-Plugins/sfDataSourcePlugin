<?php

/*
 * This file is part of the symfony package.
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

class ProjectConfiguration extends sfProjectConfiguration
{
//  protected $plugins = array('sfPropel15Plugin');
  
  public function setup()
  {
    $this->setPluginPath('sfPropel15Plugin', dirname(__FILE__).'/../../../../sfPropel15Plugin');
    $this->setPluginPath('sfPropelObjectPathBehaviorPlugin', dirname(__FILE__).'/../../../../sfPropelObjectPathBehaviorPlugin');
//    $this->setPluginPath('sfDataSourcePlugin', dirname(__FILE__).'/../../..');
    
    $this->enablePlugins('sfPropel15Plugin', 'sfPropelObjectPathBehaviorPlugin') ;//, 'sfDataSourcePlugin');
  }
}
new ProjectConfiguration();

$propelParameters = array(
  'dsn'        => 'sqlite::memory:',
	'pooling' => true,
//  'persistent' => true,
);

$database = new sfPropelDatabase($propelParameters);

$connection = $database->getConnection();
// setup database
$connection->exec("
  CREATE TABLE [city]
  (
  	[id] INTEGER  NOT NULL PRIMARY KEY,
  	[name] VARCHAR(255),
  	[country_id] INTEGER,
  	[created_at] TIMESTAMP
  );
  
  CREATE TABLE [country]
  (
  	[id] INTEGER  NOT NULL PRIMARY KEY,
  	[name] VARCHAR(255),
  	[created_at] TIMESTAMP
  );
");

// load models
$autoload->addDirectory('/Users/leonvanderree/Zend/workspaces/DefaultWorkspace7/playground/lib/model'); //realpath(dirname(__FILE__).'/../../lib'));

// initialize database
$country_nl = new Country();
$country_nl->setName('the Netherlands');

$country_fr = new Country();
$country_fr->setName('France');


$city_ams = new City();
$city_ams->setCountry($country_nl);
$city_ams->setName('Amsterdam');
$city_ams->save();

$city_rdm = new City();
$city_rdm->setCountry($country_nl);
$city_rdm->setName('Rotterdam');
$city_rdm->save();

$city_rdm = new City();
$city_rdm->setCountry($country_nl);
$city_rdm->setName('Den Haag');
$city_rdm->save();

$city_prs = new City;
$city_prs->setName('Paris');
$city_prs->setCountry($country_fr);
$city_prs->save();

$city_bdx = new City;
$city_bdx->setCountry($country_fr);
$city_bdx->setName('Bordeaux');
$city_bdx->save();

function iterator_to_field_array($iterator, $field)
{
  $values = array();
  foreach ($iterator as $key => $value)
  {
    $values[] = $iterator[$field];
  }
  return $values;
}


function iterator_ids_to_field_array($iterator)
{
  $values = array();
  foreach ($iterator as $key => $value)
  {
    $values[] = $value->getId();
  }
  return $values;
}


$t = new lime_test(40, new lime_output_color());

$t->diag('->__construct()');

$citySource = new sfDataSourcePropel('City');

try
{
  $citySource->requireColumn('Id');
  $t->pass('->requireColumn() doesn\'t throw an error since it ignores field-checks anyway');
}
catch (Exception $e)
{
  $t->fail('->requireColumn() doesn\'t throw an error since it ignores field-checks anyway');
}

try
{
  $citySource->requireColumn('Country.Id');
  $t->pass('->requireColumn() Relation to Country found');
}
catch (Exception $e)
{
  $t->fail('->requireColumn() Relation to Country found');
}


try
{
  $citySource->requireColumn('Illegal.Id');
   $t->fail('->requireColumn() throws an exception since there is no relation Illegal');
}
catch (PropelException $e)
{
  $t->pass('->requireColumn() throws an exception since there is no relation Illegal');
}
catch (Exception $e)
{
  $t->fail('->requireColumn() throws an exception since there is no relation Illegal');
}



$city = $citySource->current();
$t->is($city->getId(), 1, '->__construct() accepts a Propel class name as argument');


try
{
  $s = new sfDataSourcePropel('foobar');
  $t->fail('->__construct() throws an "UnexpectedValueException" if the given class name is no Propel class');
}
catch (UnexpectedValueException $e)
{
  $t->pass('->__construct() throws an "UnexpectedValueException" if the given class name is no Propel class');
}


try
{
  $s = new sfDataSourcePropel(new stdClass());
  $t->fail('->__construct() throws an "InvalidArgumentException" if the argument is not valid');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->__construct() throws an "InvalidArgumentException" if the argument is not valid');
}

// ->current()
$t->diag('->current()');
$s = new sfDataSourcePropel('City');
$current = $s->current();
$t->is($current->getId(), 1, '->current() returns the first result');
$s->next();
$current = $s->current();
$t->is($current->getId(), 2, '->current() returns the current result when iterating');


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
$s = new sfDataSourcePropel('City');
$t->is(array_keys(iterator_to_array($s)), range(0, 4), 'sfDataSourcePropel implements the SeekableIterator interface');
$t->is(count(iterator_to_array($s)), 5, 'sfDataSourcePropel implements the SeekableIterator interface');

$s = new sfDataSourcePropel('City');
$s->seek(1);
$t->is($s->current()->getId(), 2, 'sfDataSourcePropel implements the SeekableIterator interface');
$t->is($s->current()->getName(), 'Rotterdam', 'sfDataSourcePropel implements the SeekableIterator interface');

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
$s = new sfDataSourcePropel('City');
$t->is(count($s), 5, 'sfDataSourcePropel implements the Countable interface');
$s->setLimit(2);
$t->is(count($s), 2, 'sfDataSourcePropel implements the Countable interface');
$s->setOffset(4);
$t->is(count($s), 1, 'sfDataSourcePropel implements the Countable interface');

// ->countAll()
$t->diag('->countAll()');
$s = new sfDataSourcePropel('City');
$t->is($s->countAll(), 5, '->countAll() returns the total amount of records');
$s->setLimit(2);
$t->is($s->countAll(), 5, '->countAll() returns the total amount of records');
$s->setOffset(2);
$t->is($s->countAll(), 5, '->countAll() returns the total amount of records');

// ->offsetGet()
$t->diag('->offsetGet()');
$s = new sfDataSourcePropel('City');
$s->setLimit(2);
$t->is(iterator_to_field_array($s, 'Id'), array(1,2), '->offsetGet() returns correct field-values');


// ->offsetGet() recursion
$t->diag('->offsetGet() recursion');
$s = new sfDataSourcePropel('City');
$s->setLimit(2);
$t->is(iterator_to_field_array($s, 'Country.Id'), array(1,1), '->offsetGet() recusively parses objectPath, and returns correct related field-values');


// ->setOffset()
$t->diag('->setOffset()');
$s = new sfDataSourcePropel('City');
$s->setOffset(1);
$t->is(iterator_ids_to_field_array($s), range(2,5), '->setOffset() sets the offset of the iterator');

$s->setOffset(30);
$t->is(iterator_ids_to_field_array($s), array(), '->setOffset() sets the offset of the iterator');

$s->setOffset(1);
$s->seek(1);
$t->is($s['Id'], 3, '->setOffset() sets the offset of the iterator');
$t->is($s['Name'], 'Den Haag', '->setOffset() sets the offset of the iterator');

// ArrayAccess interface
$t->diag('ArrayAccess interface');
$s = new sfDataSourcePropel('City');
$t->is($s['Id'], 1, 'sfDataSourcePropel implements the ArrayAccess interface');
$t->is($s['Name'], 'Amsterdam', 'sfDataSourcePropel implements the ArrayAccess interface');
$t->ok(isset($s['Id']), 'sfDataSourcePropel implements the ArrayAccess interface');
$t->ok(!isset($s['foobar']), 'sfDataSourcePropel implements the ArrayAccess interface');
$s->next();
$t->is($s['Id'] , 2, 'sfDataSourcePropel implements the ArrayAccess interface');
$t->is($s['Name'], 'Rotterdam', 'sfDataSourcePropel implements the ArrayAccess interface');

try
{
  $s['Name'] = 'Foobar';
  $t->fail('sfDataSourcePropel throws a "LogicException" when fields are set using ArrayAccess');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourcePropel throws a "LogicException" when fields are set using ArrayAccess');
}
try
{
  unset($s['Name']);
  $t->fail('sfDataSourcePropel throws a "LogicException" when fields are unset using ArrayAccess');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourcePropel throws a "LogicException" when fields are unset using ArrayAccess');
}

foreach ($s as $k => $v);
try
{
  $s->current()->getName();
  $t->fail('sfDataSourcePropel throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourcePropel throws an "OutOfBoundsException" when fields are accessed after iterating');
}
try
{
  isset($s['Name']);
  $t->fail('sfDataSourcePropel throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourcePropel throws an "OutOfBoundsException" when fields are accessed after iterating');
}

// ->setSort()
$t->diag('->setSort()');
$s = new sfDataSourcePropel('City');
$originalValues = array(
  'Amsterdam',
  'Rotterdam',
  'Den Haag',
  'Paris',
  'Bordeaux',
);

$s->setSort('Name', sfDataSourceInterface::DESC);
rsort($originalValues);
$t->is(iterator_to_field_array($s, 'Name'), $originalValues, '->setSort() sorts correctly');

$s = new sfDataSourcePropel('City');
// TODO: sorting on the same column twice will result in first sorting on first column, than on the second (which will be ineffective)
// is this desired?
$s->setSort('Name', sfDataSourceInterface::DESC);  
$s->setSort('Name', sfDataSourceInterface::ASC);
sort($originalValues);
$t->is(array_reverse(iterator_to_field_array($s, 'Name')), $originalValues, '->setSort() sorts correctly, or isn\'t it?');


$byCountryValues = array(
  'Paris',
  'Bordeaux',
  'Amsterdam',
  'Rotterdam',
  'Den Haag',
);

$s = new sfDataSourcePropel('City');
$s->setSort('Country.Name', sfDataSourceInterface::ASC);
$t->is(iterator_to_field_array($s, 'Name'), $byCountryValues, '->setSort() sorts correctly');

