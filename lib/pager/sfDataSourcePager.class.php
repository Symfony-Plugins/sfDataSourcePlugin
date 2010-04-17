<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class allows the pagination of any data sources implementing
 * sfDataSourceInterface. You can use the methods of this class both to
 * easily adjust offsets and limits in the underlying data source and to
 * control the creation of a pager interface.
 *
 * Pass the desired data source and the maximum value of records per page
 * to the constructor. Then you can use the methods setPage(), setMaxPerPage()
 * and setPageLimit() to adjust the behaviour of the pager.
 *
 * <code>
 * $source = new sfDataSourceDoctrine('User');
 * $pager = new sfDataSourcePager($source, 25);
 * $pager->setPage(2);
 *
 * foreach ($pager->getDataSource() as $user)
 * {
 *   echo $user->username;
 * }
 * </code>
 *
 * This class features a lot of methods that simplify the creation of a pager
 * interface. In most pager interfaces there is a lot of logic controlling
 * whether the arrow pointing to the next or the previous page is displayed etc.
 * All of this logic is encapsulated in this class.
 *
 * <code>
 * <?php if ($pager->hasFirstPage()): ?>
 *   <a href="?page=<?php echo $pager->getFirstPage() ?>">|&laquo;</a>
 * <?php endif ?>
 * <?php if ($pager->hasPreviousPage()): ?>
 *   <a href="?page=<?php echo $pager->getPreviousPage() ?>">&laquo;</a>
 * <?php endif ?>
 * <?php foreach ($pager as $page): ?>
 *   <?php if ($pager->isCurrentPage($page)): ?>
 *     [<?php echo $page ?>]
 *   <?php else: ?>
 *     <a href="?page=<?php echo $page ?>"><?php echo $page ?></a>
 *   <?php endif ?>
 * <?php endforeach ?>
 * <?php if ($pager->hasPreviousPage()): ?>
 *   <a href="?page=<?php echo $pager->getPreviousPage() ?>">&raquo;</a>
 * <?php endif ?>
 * <?php if ($pager->hasLastPage()): ?>
 *   <a href="?page=<?php echo $pager->getLastPage() ?>">&raquo;|</a>
 * <?php endif ?>
 * </code>
 *
 * The above code will render a pager similar to
 *
 * [1] 2 3 4 5 6 7 8 9 » »|
 *
 * Most of the time you will not want to display links to all pages, but only
 * a limited number of them depending on the current page. This is what the
 * method setPageLimit() is for.
 *
 * <code>
 * $pager->setPage(4);
 * $pager->setPageLimit(5);
 * </code>
 *
 * |« « 2 3 [4] 5 6 » »|
 *
 * @package    symfony
 * @subpackage grid
 * @author     Bernhard Schussek <bschussek@gmail.com>
 * @version    SVN: $Id$
 */
class sfDataSourcePager implements Iterator
{
  const DEFAULT_MAX_ITEMS_PER_PAGE = 10;

  protected
    $source      = null,
    $maxPerPage  = 0,
    $page,
    $pages       = array(),
    $pageLimit   = 0,
    $recordCount = -1,
    $cursor      = 0;

  /**
   * Constructor.
   *
   * The given data source will be cloned internally because it needs to be
   * modified.
   *
   * @param  sfDataSourceInterface $source      The data source for the pager
   * @param  integer               $maxPerPage  The maximum number of rows per
   *                                            page. Default: 10
   *
   * @see setMaxPerPage()
   */
  public function __construct(sfDataSourceInterface $source, $maxPerPage = self::DEFAULT_MAX_ITEMS_PER_PAGE)
  {
    $this->source = clone $source;

    $this->setMaxPerPage($maxPerPage);
  }

  /**
   * Adjusts the maximum number of rows per page.
   *
   * @param  integer $amount  The maximum number of rows per page. If set to 0
   *                          (=no limit), all rows will be rendered on one page.
   * @throws DomainException  Throws an exception if the given amount is smaller
   *                          than zero
   */
  public function setMaxPerPage($amount)
  {
    if ($amount < 0)
    {
      throw new DomainException(sprintf('The maximum amount of records per page (%s) must be 0 or greater', $amount));
    }

    if (isset($this->page))
    {
      throw new Exception('You should call setMaxPerPage directly after the construction of the page object,
                           or at least before setting the current page');
    }

    $this->maxPerPage = $amount;
    $this->source->setLimit($amount);

  }

  /**
   * Returns the maximum number of rows per page given to the constructor or
   * set with setMaxPerPage().
   *
   * @return integer The maximum number of rows per page
   */
  public function getMaxPerPage()
  {
    return $this->maxPerPage;
  }

  /**
   * Returns the data source of the pager. You can iterate over this data
   * source to access the records of the current page.
   *
   * @return sfDataSourceInterface
   */
  public function getDataSource()
  {
    return $this->source;
  }

  /**
   * Returns total the number of pages. This method will always return 1 if
   * maximum number of rows per page is set to 0 (=no limit).
   *
   * @return integer The number of pages
   */
  public function getPageCount()
  {
    // cache the page count since countAll() might use resources on every call
    if ($this->getMaxPerPage() > 0
        &&
        $this->getRecordCount() > 0)
    {
      return (int)ceil($this->getRecordCount() / (float)$this->getMaxPerPage());
    }
    else
    {
      return 1;
    }
  }

  /**
   * Returns the total number of records in the data source.
   *
   * @return integer The number of records
   */
  public function getRecordCount()
  {
    if ($this->recordCount == -1)
    {
      $this->recordCount = $this->source->countAll();
    }

    return $this->recordCount;
  }

  /**
   * Returns whether the pager needs to paginate, i.e., whether the data source
   * spans over more than one page.
   *
   * @return boolean Whether pagination is required
   */
  public function hasToPaginate()
  {
    return $this->getPageCount() > 1;
  }
  /*
   * same as hasToPaginate, but Symfony sfPager has defined haveToPaginate()
   * TODO: maybe remove hasToPaginate...
   *
    @return boolean Whether pagination is required
   */
  public function haveToPaginate()
  {
    return $this->hasToPaginate();
  }


  /**
   * Sets the current page of the pager. The offset and limit values of the
   * underlying data source will be adjusted accordingly.
   *
   * If the given value is smaller than 1, the page will be set to the first
   * page. If the given value is greater than the number of pages, the page will
   * be set to the last page.
   *
   * @param integer $page The current page. The first page has index 1
   */
  public function setPage($page)
  {
    $this->page = (int) $page;
  }

  /**
   * Returns the current page set with setPage(). If no page has been set, this
   * method returns 1.
   *
   * @return integer The current page. The first page has index 1
   */
  public function getPage()
  {
    if (!isset($this->page))
    {
      $this->page = 1;
    }
    else
    {
      if ($this->page > $this->getPageCount())
      {
        $this->page = $this->getPageCount();
      }

      if ($this->page < 1)
      {
        $this->page = 1;
      }
    }

    return $this->page;
  }

  /**
   * Returns the index of the first record on the current page. The first
   * record on the first page has index 0. This in contradiction to pages,
   * that are based on index 1
   *
   * @return integer The index of the first record on the current page
   */
  public function getFirstIndex()
  {
    return ($this->getPage()-1)*$this->getMaxPerPage();
  }

  /**
   * Returns the index of the last record on the current page. The first
   * record on the first page has index 0. This in contradiction to pages,
   * that are based on index 1
   *
   * @return integer The index of the last record on the current page
   */
  public function getLastIndex()
  {
    return min($this->getPage()*$this->getMaxPerPage(), $this->getRecordCount())-1;
  }

  /**
   * Returns the index of the first page. This method returns always 1.
   *
   * @return integer The index of the first page
   */
  public function getFirstPage()
  {
    return 1;
  }

  /**
   * Returns the index of the previous page or of the first page, if no
   * previous page exists.
   *
   * @return integer The index of the previous page
   */
  public function getPreviousPage()
  {
    return (int)max($this->getPage() - 1, $this->getFirstPage());
  }

  /**
   * Returns the index of the next page or of the last page, if no
   * next page exists.
   *
   * @return integer The index of the next page
   */
  public function getNextPage()
  {
    return (int)min($this->getPage() + 1, $this->getLastPage());
  }

  /**
   * Returns the index of the last page. If only one page exists, this index
   * is equal to the index of the first page.
   *
   * @return integer The index of the last page
   */
  public function getLastPage()
  {
    return $this->getPageCount();
  }

  /**
   * Returns whether a navigation element pointing to the first page should
   * be rendered. This method returns TRUE if the current page is 3 or higher.
   *
   * @return boolean Whether the first page should be linked
   */
  public function hasFirstPage()
  {
    return $this->hasPreviousPage() && $this->getPreviousPage() !== $this->getFirstPage();
  }

  /**
   * Returns whether a navigation element pointing to the previous page should
   * be rendered. This method returns TRUE if the current page is 2 or higher.
   *
   * @return boolean Whether the previous page should be linked
   */
  public function hasPreviousPage()
  {
    return !$this->isCurrentPage($this->getFirstPage());
  }

  /**
   * Returns whether a navigation element pointing to the next page should
   * be rendered. This method returns TRUE if the current page is (last-1) or lower.
   *
   * @return boolean Whether the next page should be linked
   */
  public function hasNextPage()
  {
    return !$this->isCurrentPage($this->getLastPage());
  }

  /**
   * Returns whether a navigation element pointing to the last page should
   * be rendered. This method returns TRUE if the current page is (last-2) or lower.
   *
   * @return boolean Whether the last page should be linked
   */
  public function hasLastPage()
  {
    return $this->hasNextPage() && $this->getNextPage() !== $this->getLastPage();
  }

  /**
   * Returns whether the given page is the current page set by setPage()
   *
   * @param  integer $page  The page to check for
   * @return boolean        Whether $page is equal to the current page
   */
  public function isCurrentPage($page)
  {
    return $this->getPage() == $page;
  }

  /**
   * Sets the maximum number of pages processed by the iterator. This method
   * is very effective to limit the number of linked pages in the navigation.
   *
   * You can set the limit to 0 (=no limit) to disable the page limit.
   *
   * @param integer $limit The maximum number of iterated pages
   */
  public function setPageLimit($limit)
  {
    if ($limit < 0)
    {
      throw new DomainException(sprintf('The page limit (%s) must be 0 or greater', $limit));
    }

    $this->pageLimit = $limit;
  }

  /**
   * Returns the page limit set with setPageLimit(). If no page limit has been
   * set, this method returns 0 (=no limit).
   *
   * @return integer The maximum number of iterated pages
   */
  public function getPageLimit()
  {
    return $this->pageLimit;
  }

  /**
   * Returns the page currently processed by the iterator. The index of the
   * first processed page depends on the current page set with setPage() and
   * on the limit of visible pages set with setPageLimit(). If no limit is set,
   * the first page will always be page 1.
   *
   * @return integer The index of the current page
   */
  public function current()
  {
    return $this->pages[$this->cursor];
  }

  /**
   * Advances the iterator to the next page.
   */
  public function next()
  {
    ++$this->cursor;
  }

  /**
   * Returns the cursor of the iterator. While page values depend on the
   * current page set with setPage() and on the page limit set with setPageLimit(),
   * the cursor of the first processed page will always be 0.
   *
   * @return integer The cursor of the iterator
   */
  public function key()
  {
    return $this->cursor;
  }

  /**
   * Returns whether the cursor of the iterator still points on a valid page.
   *
   * @return boolean Whether the cursor is valid
   */
  public function valid()
  {
    return $this->cursor < count($this->pages);
  }

  /**
   * Resets the cursor of the iterator to the first page that should be
   * displayed. The index of this page depends on the current page set with
   * setPage() and on the page limit set with setPageLimit(). If no limit is
   * set, the first page will always be page 1.
   */
  public function rewind()
  {
    $this->cursor = 0;

    if ($this->getPageLimit() != 0)
    {
      $offset = $this->getPage() - (int)floor($this->getPageLimit()/2);

      // we don't want page numbers < 1
      if ($offset < 1)
      {
        $offset = 1;
      }
      // we don't want page numbers > total page count
      if ($offset+$this->getPageLimit() > $this->getPageCount())
      {
        $offset = $this->getPageCount() - $this->getPageLimit() + 1;
      }

      $this->pages = range($offset, $offset + $this->getPageLimit() - 1);
    }
    else
    {
      $this->pages = range(1, $this->getPageCount());
    }
  }
}