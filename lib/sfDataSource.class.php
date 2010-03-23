<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class contains common methods of data sources implementing
 * sfDataSourceInterface. You can extend it if you need its methods in your
 * own data source.
 *
 * @package    symfony
 * @subpackage grid
 * @author     Bernhard Schussek <bschussek@gmail.com>
 * @version    SVN: $Id$
 */
abstract class sfDataSource implements sfDataSourceInterface
{
  private
    $cursor = 0,
    $offset = 0,
    $limit = 0;

  /**
   * Implements the sorting algorithm for this data source. You can expect
   * that the parameter values are valid. This method is called from setSort().
   *
   * @param  string $column The name of the column by which to sort
   * @param  string $order  The order in which to sort. Is either
   *                        sfDataSourceInterface::ASC or sfDataSourceInterface::DESC
   */
  abstract protected function doSort($column, $order);

  /**
   * @throws DomainException  Throws an exception if the given value for $order
   *                          is not one of sfDataSourceInterface::ASC or
   *                          sfDataSourceInterface::DESC.
   * @throws LogicException   Throws an exception if the given value for $column
   *                          is not an existing column name.
   *
   * @see sfGridFormatterInterface::setSort()
   */
  public function setSort($column, $order = sfDataSourceInterface::ASC)
  {
    if ($order !== sfDataSourceInterface::ASC && $order !== sfDataSourceInterface::DESC)
    {
      throw new DomainException(sprintf('The value "%s" is no valid sort order. Should be sfDataSourceInterface::ASC or sfDataSourceInterface::DESC', $order));
    }
    $this->requireColumn($column);

    $this->doSort($column, $order);

    $this->rewind();
  }

  /**
   * This method may not be called.
   *
   * @throws LogicException Always throws an exception
   */
  final public function offsetSet($column, $value)
  {
    throw new LogicException('Cannot modify data source fields (read-only)');
  }

  /**
   * This method may not be called.
   *
   * @throws LogicException Always throws an exception
   */
  final public function offsetUnset($column)
  {
    throw new LogicException('Cannot unset data source fields (read-only)');
  }

  /**
   * Returns whether the given column exists in the current row. If the internal
   * row pointer has exceeded the number of rows, an exception is thrown. You
   * can call valid() to check whether the row pointer is still valid.
   *
   * @param string $column The name of the column to check for
   */
  public function offsetExists($column)
  {
    if (!$this->valid())
    {
      throw new OutOfBoundsException(sprintf('The result with index "%s" does not exist', $this->cursor));
    }

    try
    {
      $this->requireColumn($column);
    }
    catch (LogicException $e)
    {
      return false;
    }

    return true;
  }

  /**
   * @throws DomainException Throws an exception if the given offset is smaller
   *                         than zero
   *
   * @see sfDataSourceInterface::setOffset()
   */
  public function setOffset($offset)
  {
    if ($offset < 0)
    {
      throw new DomainException(sprintf('The record offset (%s) must be 0 or greater', $offset));
    }

    $this->offset = $offset;
  }

  /**
   * Returns the offset set by setOffset(). If no offset has been set, 0 is
   * returned.
   *
   * @return integer The number of rows skipped at the beginning of the data
   *                 source when iterating
   */
  public function getOffset()
  {
    return $this->offset;
  }

  /**
   * @throws DomainException Throws an exception if the given offset is smaller
   *                         than zero
   *
   * @see sfDataSourceInterface::setLimit()
   */
  public function setLimit($limit)
  {
    if ($limit < 0)
    {
      throw new DomainException(sprintf('The record limit (%s) must be 0 or greater', $limit));
    }

    $this->limit = $limit;
  }

  /**
   * Returns the row limit set using setLimit(). If no limit has been set, 0
   * is returned.
   *
   * @return integer The maximum number of rows being iterated. If set to 0,
   *                 all rows are iterated
   */
  public function getLimit()
  {
    return $this->limit;
  }

  /**
   * Returns the value of the internal row pointer. The first row always has
   * key 0, independent of any offset set with setOffset().
   *
   * <code>
   * $source->setOffset(5);
   * $source->rewind()
   * echo $source->key(); // returns "0"
   * </code>
   *
   * @return integer The internal row pointer
   */
  public function key()
  {
    return $this->cursor;
  }

  /**
   * Resets the internal row pointer to 0.
   */
  public function rewind()
  {
    $this->cursor = 0;
  }

  /**
   * Increases the internal row pointer by 1.
   */
  public function next()
  {
    ++$this->cursor;
  }

  /**
   * Returns whether the internal row pointer still points on a valid row.
   *
   * @return boolean Whether the internal row pointer is still valid
   */
  public function valid()
  {
    return $this->cursor < $this->count();
  }

  /**
   * Sets the internal row pointer to the given row index. The index may be
   * any value in the interval from 0 to count()-1.
   *
   * If you want to skip a number of rows in the beginning of the data source,
   * you should rather use the function setOffset() which can improve data
   * processing performance.
   *
   * @param integer $index The index to which to set the internal row pointer
   */
  public function seek($index)
  {
    if ($index >= $this->count() || $index < 0)
    {
      throw new OutOfBoundsException(sprintf('The result with index "%s" does not exist', $index));
    }

    $this->cursor = $index;
  }
}