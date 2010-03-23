<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

class sfDataSourceTest extends sfDataSource
{
  public
    $sortColumn = null,
    $sortOrder = null;

  public function requireColumn($column)
  {
    if ($column != 'id')
    {
      throw new LogicException(sprintf('The column "%s" has not been defined in the datasource', $column));
    }
  }

  /**
   * @see sfDataSourceInterface
   */
  public function setFilter($fields)
  {
    throw new Exception('This method has not been implemented yet');
  }


  protected function doSort($column, $order)
  {
    $this->sortColumn = $column;
    $this->sortOrder = $order;
  }

  public function current() {}
  public function offsetGet($key) {}
  public function count() {}
  public function countAll() {}
}

$t = new lime_test(14, new lime_output_color());

// ->setSort()
$t->diag('->setSort()');
$s = new sfDataSourceTest();
$s->setSort('id');
$t->is($s->sortColumn, 'id', '->setSort() sets the sort order to sfDataSourceInterface::DESC by default');
$t->is($s->sortOrder, sfDataSourceInterface::ASC, '->setSort() sets the sort order to sfDataSourceInterface::DESC by default');

$s = new sfDataSourceTest();
$s->setSort('id', sfDataSourceInterface::DESC);
$t->is($s->sortOrder, sfDataSourceInterface::DESC, '->setSort() accepts the constant sfDataSourceInterface::DESC');

$s = new sfDataSourceTest();
$s->setSort('id', sfDataSourceInterface::ASC);
$t->is($s->sortOrder, sfDataSourceInterface::ASC, '->setSort() accepts the constant sfDataSourceInterface::ASC');

$s = new sfDataSourceTest();
$s->setSort('id', 'desc');
$t->is($s->sortOrder, sfDataSourceInterface::DESC, '->setSort() accepts the string "desc"');

$s = new sfDataSourceTest();
$s->setSort('id', 'asc');
$t->is($s->sortOrder, sfDataSourceInterface::ASC, '->setSort() accepts the string "asc"');

try
{
  $s = new sfDataSourceTest();
  $s->setSort('id', 'foobar');
  $t->fail('->setSort() throws a "DomainException" when the sort order is invalid');
}
catch (DomainException $e)
{
  $t->pass('->setSort() throws a "DomainException" when the sort order is invalid');
}

try
{
  $s = new sfDataSourceTest();
  $s->setSort('foobar');
  $t->fail('->setSort() throws a "LogicException" when the given column does not exist');
}
catch (LogicException $e)
{
  $t->pass('->setSort() throws a "LogicException" when the given column does not exist');
}

// ->setLimit(), ->getLimit()
$t->diag('->setLimit(), ->getLimit()');
$s = new sfDataSourceTest();
$s->setLimit(0);
$t->is($s->getLimit(), 0, '->setLimit() sets the limit correctly');
$s->setLimit(20);
$t->is($s->getLimit(), 20, '->setLimit() sets the limit correctly');
try
{
  $s->setLimit(-1);
  $t->fail('->setLimit() throws a "DomainException" when the given value is < 0');
}
catch (DomainException $e)
{
  $t->pass('->setLimit() throws a "DomainException" when the given value is < 0');
}

// ->setOffset(), ->getOffset()
$t->diag('->setOffset(), ->getOffset()');
$s = new sfDataSourceTest();
$t->is($s->getOffset(), 0, '->getOffset() returns 0 when no offset has been set');
$s->setOffset(3);
$t->is($s->getOffset(3), 3, '->setOffset() sets the offset correctly');
try
{
  $s->setOffset(-1);
  $t->fail('->setOffset() throws a "DomainException" when the given value is < 0');
}
catch (DomainException $e)
{
  $t->pass('->setOffset() throws a "DomainException" when the given value is < 0');
}
