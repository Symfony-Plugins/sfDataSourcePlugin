<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../bootstrap/unit.php');
require_once(dirname(__FILE__).'/../mock/sfDataSourceMock.class.php');

class sfDataSourcePagerTest extends sfDataSourcePager
{
  public $maxPerPage = 0;

  public function setMaxPerPage($amount)
  {
    $this->maxPerPage = $amount;
    return parent::setMaxPerPage($amount);
  }
}

$t = new lime_test(70, new lime_output_color());

// ->getDataSource()
$t->diag('->getDataSource()');
$p = new sfDataSourcePager($s = new sfDataSourceMock(), 3);
$t->ok($p->getDataSource() !== $s, '->getDataSource() returns a clone of the data source given to the pager\'s constructor');
$t->isa_ok($p->getDataSource(), 'sfDataSourceMock', '->getDataSource() returns a clone of the data source given to the pager\'s constructor');

// ->setMaxPerPage()
$t->diag('->setMaxPerPage(), ->getMaxPerPage()');
$p = new sfDataSourcePager($s = new sfDataSourceMock(), 0);
$p->setMaxPerPage(3);
$t->is($p->getMaxPerPage(), 3, '->getMaxPerPage() returns the maximum number of records per page');
$t->is($p->getDataSource()->limit, 3, '->setMaxPerPage() sets the limit on the data source to the value of $amount');
$t->is($s->limit, null, '->setMaxPerPage() does not set the limit on the original data source');
try
{
  $p->setMaxPerPage(-1);
  $t->fail('->setMaxPerPage() throws a "DomainException" if the given value is < 0');
}
catch (DomainException $e)
{
  $t->pass('->setMaxPerPage() throws a "DomainException" if the given value is < 0');
}

// ->__construct()
$t->diag('->__construct()');
$p = new sfDataSourcePagerTest($s = new sfDataSourceMock(), 3);
$t->is($p->maxPerPage, 3, '->__construct() forwards its second parameter to ->setMaxPerPage()');

// ->getPageCount()
$t->diag('->getPageCount()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 100);
$t->is($p->getPageCount(), 1, '->getPageCount() returns the number of pages');
$p = new sfDataSourcePager(new sfDataSourceMock(), 0);
$t->is($p->getPageCount(), 1, '->getPageCount() returns the number of pages');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$t->is($p->getPageCount(), 5, '->getPageCount() returns the number of pages');
$p = new sfDataSourcePager(new sfDataSourceMock(), 3);
$t->is($p->getPageCount(), 3, '->getPageCount() returns the number of pages');

// ->hasToPaginate()
$t->diag('->hasToPaginate()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 100);
$t->ok(!$p->hasToPaginate(), '->hasToPaginate() returns whether the pager has to paginate');
$p = new sfDataSourcePager(new sfDataSourceMock(), 0);
$t->ok(!$p->hasToPaginate(), '->hasToPaginate() returns whether the pager has to paginate');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$t->ok($p->hasToPaginate(), '->hasToPaginate() returns whether the pager has to paginate');

// ->setPage(), ->getPage()
$t->diag('->setPage(), ->getPage()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$t->is($p->getPage(), 1, '->getPage() returns 1 if no page has been set');
$p->setPage(-1);
$t->is($p->getPage(), 1, '->setPage() sets the page to the first if the given value is smaller than zero');
$p->setPage(2);
$t->is($p->getPage(), 2, '->setPage() sets the page correctly');

// I don't think this is desired, page should only be changed until we are rendering
//$t->is($p->getDataSource()->offset, 2, '->setPage() sets the offset on the data source');

$t->is($s->offset, null, '->setPage() does not set the offset on the original data source');
$p->setPage(10);
$t->is($p->getPage(), 5, '->setPage() sets the page to the last if the given value is greater than the page count');
// I don't think this is desired, page should only be changed until we are rendering
//$t->is($p->getDataSource()->offset, 8, '->setPage() sets the offset on the data source');

// ->getFirstPage(), ->getPreviousPage(), ->getNextPage(), ->getLastPage()
$t->diag('->getFirstPage(), ->getPreviousPage(), ->getNextPage(), ->getLastPage()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$p->setPage(1);
$t->is($p->getFirstPage(), 1, '->getFirstPage() returns the first page');
$t->is($p->getPreviousPage(), 1, '->getPreviousPage() returns the previous page');
$t->is($p->getNextPage(), 2, '->getNextPage() returns the next page');
$t->is($p->getLastPage(), 5, '->getLastPage() returns the last page');
$p->setPage(2);
$t->is($p->getFirstPage(), 1, '->getFirstPage() returns the first page');
$t->is($p->getPreviousPage(), 1, '->getPreviousPage() returns the previous page');
$t->is($p->getNextPage(), 3, '->getNextPage() returns the next page');
$t->is($p->getLastPage(), 5, '->getLastPage() returns the last page');
$p->setPage(4);
$t->is($p->getFirstPage(), 1, '->getFirstPage() returns the first page');
$t->is($p->getPreviousPage(), 3, '->getPreviousPage() returns the previous page');
$t->is($p->getNextPage(), 5, '->getNextPage() returns the next page');
$t->is($p->getLastPage(), 5, '->getLastPage() returns the last page');
$p->setPage(5);
$t->is($p->getFirstPage(), 1, '->getFirstPage() returns the first page');
$t->is($p->getPreviousPage(), 4, '->getPreviousPage() returns the previous page');
$t->is($p->getNextPage(), 5, '->getNextPage() returns the next page');
$t->is($p->getLastPage(), 5, '->getLastPage() returns the last page');

// ->isCurrentPage()
$t->diag('->isCurrentPage()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$p->setPage(2);
$t->ok($p->isCurrentPage(2), '->isCurrentPage() returns whether the given page is the current page');
$t->ok(!$p->isCurrentPage(1), '->isCurrentPage() returns whether the given page is the current page');

// ->hasFirstPage(), ->hasPreviousPage(), ->hasNextPage(), ->hasLastPage()
$t->diag('->hasFirstPage(), ->hasPreviousPage(), ->hasNextPage(), ->hasLastPage()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$p->setPage(1);
$t->ok(!$p->hasFirstPage(), '->hasFirstPage() returns false when the current page or the previous page is the first page');
$t->ok(!$p->hasPreviousPage(), '->hasPreviousPage() returns false when the current page is the first page');
$t->ok($p->hasNextPage(), '->hasNextPage() returns true when the current page is not the last page');
$t->ok($p->hasLastPage(), '->hasLastPage() returns true when the next page is not the last page');
$p->setPage(2);
$t->ok(!$p->hasFirstPage(), '->hasFirstPage() returns false when the previous page is the first page');
$t->ok($p->hasPreviousPage(), '->hasPreviousPage() returns true when the current page is not the first page');
$t->ok($p->hasNextPage(), '->hasNextPage() returns true when the current page is not the last page');
$t->ok($p->hasLastPage(), '->hasLastPage() returns true when the next page is not the last page');
$p->setPage(4);
$t->ok($p->hasFirstPage(), '->hasFirstPage() returns false when the previous page is not the first page');
$t->ok($p->hasPreviousPage(), '->hasPreviousPage() returns true when the current page is not the first page');
$t->ok($p->hasNextPage(), '->hasNextPage() returns true when the current page is not the last page');
$t->ok(!$p->hasLastPage(), '->hasLastPage() returns false when the next page is the last page');
$p->setPage(5);
$t->ok($p->hasFirstPage(), '->hasFirstPage() returns false when the previous page is not the first page');
$t->ok($p->hasPreviousPage(), '->hasPreviousPage() returns true when the current page is not the first page');
$t->ok(!$p->hasNextPage(), '->hasNextPage() returns false when the current page is the last page');
$t->ok(!$p->hasLastPage(), '->hasLastPage() returns false when the current page is the last page');

// Iterator interface
$t->diag('Iterator interface');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$t->is(iterator_to_array($p), range(1,5), 'sfDataSourcePager implements the Iterator interface');

// ->setPageLimit()
$t->diag('->setPageLimit(), ->getPageLimit()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 1);
$t->is($p->getPageLimit(), 0, '->getPageLimit() returns 0 if no page limit was set');
$p->setPageLimit(5);
$t->is($p->getPageLimit(), 5, '->getPageLimit() returns the page limit');
$p->setPage(1);
$t->is(iterator_to_array($p), range(1,5), '->setPageLimit() limits the number of iterated pages');
$p->setPage(9);
$t->is(iterator_to_array($p), range(5,9), '->setPageLimit() limits the number of iterated pages');
$p->setPage(5);
$t->is(iterator_to_array($p), range(3,7), '->setPageLimit() limits the number of iterated pages');
$p->setPage(2);
$t->is(iterator_to_array($p), range(1,5), '->setPageLimit() limits the number of iterated pages');
$p->setPage(8);
$t->is(iterator_to_array($p), range(5,9), '->setPageLimit() limits the number of iterated pages');
$p->setPageLimit(0);
$t->is(iterator_to_array($p), range(1,9), '->setPageLimit() does not limit the number of iterated pages if set to 0');
try
{
  $p->setPageLimit(-1);
  $t->fail('->setPageLimit() throws a "DomainException" if the page limit is < 0');
}
catch (DomainException $e)
{
  $t->pass('->setPageLimit() throws a "DomainException" if the page limit is < 0');
}

// ->getFirstIndex(), ->getLastIndex()
$t->diag('->getFirstIndex(), ->getLastIndex()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 4);
$p->setPage(1);
$t->is($p->getFirstIndex(), 0, '->getFirstIndex() returns the index of the first element on the current page');
$t->is($p->getLastIndex(), 3, '->getLastIndex() returns the index of the last element on the current page');
$p->setPage(2);
$t->is($p->getFirstIndex(), 4, '->getFirstIndex() returns the index of the first element on the current page');
$t->is($p->getLastIndex(), 7, '->getLastIndex() returns the index of the last element on the current page');
$p->setPage(3);
$t->is($p->getFirstIndex(), 8, '->getFirstIndex() returns the index of the first element on the current page');
$t->is($p->getLastIndex(), 8, '->getLastIndex() returns the index of the last element on the current page');

// ->getRecordCount()
$t->diag('->getRecordCount()');
$p = new sfDataSourcePager(new sfDataSourceMock(), 2);
$t->is($p->getRecordCount(), 9, '->getRecordCount() returns the number of records in the data source of the pager');
