<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(37, new lime_output_color());

$data = array(
  array('id' => 1, 'name' => 'Fabien'),
  array('id' => 2, 'name' => 'Francois'),
  array('id' => 3, 'name' => 'Jonathan'),
  array('id' => 4, 'name' => 'Fabian'),
  array('id' => 5, 'name' => 'Kris'),
  array('id' => 6, 'name' => 'Nicolas'),
  array('id' => 7, 'name' => 'Dustin'),
  array('id' => 8, 'name' => 'Carl'),
);

// ->__construct()
$t->diag('->__construct()');
try
{
  new sfDataSourceArray(array(
	  array('id' => 1, 'name' => 'Fabien'), 
	  array('id' => 2, 'surname' => 'Stefan'),
	  array('id' => 3, 'name' => 'Jonathan'),
  ));
  $t->fail('->__construct() throws an "InvalidArgumentException" if not all array entries have the same keys');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->__construct() throws an "InvalidArgumentException" if not all array entries have the same keys');
}
try
{
  new sfDataSourceArray(array(
    'id' => 1, 'name' => 'Fabien', 
    'id' => 2, 'surname' => 'Stefan',
    'id' => 3, 'name' => 'Jonathan',
  ));
  $t->fail('->__construct() throws an "InvalidArgumentException" if the array entries are not arrays');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->__construct() throws an "InvalidArgumentException" if the array entries are not arrays');
}
try
{
  new sfDataSourceArray(array(
    array('id' => 1, 'name' => 'Fabien'), 
    'id' => 2, 'surname' => 'Stefan',
    'id' => 3, 'name' => 'Jonathan',
  ));
  $t->fail('->__construct() throws an "InvalidArgumentException" if the array entries are not arrays');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->__construct() throws an "InvalidArgumentException" if the array entries are not arrays');
}


$t->diag('->requireColumn()');
$s = new sfDataSourceArray($data);
try
{
  $s->requireColumn('name');
  $t->pass('sfDataSourceArray accepts existing column (name) of an array');
}
catch (Exception $e)
{
  $t->fail('sfDataSourceArray accepts existing column (name) of an array');
}

try
{
  $s->requireColumn('anyColumn');
  $t->fail('sfDataSourceArray does not accept columns that are not in the array');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourceArray does not accept columns that are not in the array');
}


// SeekableIterator interface
$t->diag('SeekableIterator interface');
$s = new sfDataSourceArray($data);
$t->is(array_keys(iterator_to_array($s)), range(0, 7), 'sfDataSourceArray implements the SeekableIterator interface');
$t->is(count(iterator_to_array($s)), 8, 'sfDataSourceArray implements the SeekableIterator interface');

$s->seek(1);
$t->is($s['id'], 2, 'sfDataSourceArray implements the SeekableIterator interface');
$t->is($s['name'], 'Francois', 'sfDataSourceArray implements the SeekableIterator interface');

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
$s = new sfDataSourceArray($data);
$t->is(count($s), 8, 'sfDataSourceArray implements the Countable interface');
$s->setLimit(4);
$t->is(count($s), 4, 'sfDataSourceArray implements the Countable interface');
$s->setOffset(5);
$t->is(count($s), 3, 'sfDataSourceArray implements the Countable interface');

// ->countAll()
$t->diag('->countAll()');
$s = new sfDataSourceArray($data);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');
$s->setLimit(4);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');
$s->setOffset(5);
$t->is($s->countAll(), 8, '->countAll() returns the total amount of records');

// ->setLimit()
$t->diag('->setLimit()');
$s = new sfDataSourceArray($data);
$s->setLimit(4);
$values = array();
foreach ($s as $row)
{
  $values[] = $row['id'];
}
$t->is($values, range(1,4), '->setLimit() limits the records returned by the iterator');

// ->setOffset()
$t->diag('->setOffset()');
$s = new sfDataSourceArray($data);
$s->setOffset(3);
$values = array();
foreach ($s as $row)
{
  $values[] = $row['id'];
}
$t->is($values, range(4,8), '->setOffset() sets the offset of the iterator');

$s->setOffset(30);
$values = array();
foreach ($s as $row)
{
  $values[] = $row['id'];
}
$t->is($values, array(), '->setOffset() sets the offset of the iterator');

$s->setOffset(2);
$s->seek(1);
$t->is($s['id'], 4, '->setOffset() sets the offset of the iterator');
$t->is($s['name'], 'Fabian', '->setOffset() sets the offset of the iterator');

// ArrayAccess interface
$t->diag('ArrayAccess interface');
$s = new sfDataSourceArray($data);
$t->is($s['id'], 1, 'sfDataSourceArray implements the ArrayAccess interface');
$t->is($s['name'], 'Fabien', 'sfDataSourceArray implements the ArrayAccess interface');
$t->ok(isset($s['id']), 'sfDataSourceArray implements the ArrayAccess interface');
$t->ok(!isset($s['foobar']), 'sfDataSourceArray implements the ArrayAccess interface');
$s->next();
$t->is($s['id'], 2, 'sfDataSourceArray implements the ArrayAccess interface');
$t->is($s['name'], 'Francois', 'sfDataSourceArray implements the ArrayAccess interface');

try
{
  $s['name'] = 'Foobar';
  $t->fail('sfDataSourceArray throws a "LogicException" when fields are set using ArrayAccess');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourceArray throws a "LogicException" when fields are set using ArrayAccess');
}
try
{
  unset($s['name']);
  $t->fail('sfDataSourceArray throws a "LogicException" when fields are unset using ArrayAccess');
}
catch (LogicException $e)
{
  $t->pass('sfDataSourceArray throws a "LogicException" when fields are unset using ArrayAccess');
}

foreach ($s as $k => $v);

try
{
  $s['name'];
  $t->fail('sfDataSourceArray throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourceArray throws an "OutOfBoundsException" when fields are accessed after iterating');
}
try
{
  isset($s['name']);
  $t->fail('sfDataSourceArray throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourceArray throws an "OutOfBoundsException" when fields are accessed after iterating');
}

// ->setSort()
$t->diag('->setSort()');
$s = new sfDataSourceArray($data);
$originalValues = array();
foreach ($data as $row) { $originalValues[] = $row['name']; }

$s->setSort('name', sfDataSourceInterface::DESC);
$values = array();
foreach ($s as $row) { $values[] = $s['name']; }
rsort($originalValues);
$t->is($values, $originalValues, '->setSort() sorts correctly');

$s->setSort('name', sfDataSourceInterface::ASC);
$values = array();
foreach ($s as $row) { $values[] = $s['name']; }
sort($originalValues);
$t->is($values, $originalValues, '->setSort() sorts correctly');




// support for empty arrays
$t->diag('support for empty arrays');

$data_empty = array();
$s = new sfDataSourceArray($data_empty);

$t->is(count($s), 0, 'sfDataSourceArray accepts empty array');

try
{
  $s->requireColumn('anyColumn');
  $t->pass('sfDataSourceArray accepts any column when an empty array is provided');
}
catch (Exception $e)
{
  $t->fail('sfDataSourceArray accepts any column when an empty array is provided');
}

try
{
  $s['name'];
  $t->fail('sfDataSourceArray throws an "OutOfBoundsException" when fields are accessed after iterating');
}
catch (OutOfBoundsException $e)
{
  $t->pass('sfDataSourceArray throws an "OutOfBoundsException" when fields are accessed after iterating');
}

