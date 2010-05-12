<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This interface allows classes to read a wide variety of two-dimensional
 * data sources such as relational databases, XML files, CSV files etc. in a
 * unified way. Two-dimensional data sources are any data sources that can
 * be structured in rows and columns.
 *
 * The methods of SeekableIterator allow to iterate all the rows of the data
 * source. The methods of ArrayAccess, on the other hand, allow to access the
 * fields in the current row in a unified way.
 *
 * <code>
 * $source = new ConcreteDataSource();
 * for ($source->rewind(); $source->valid(); $source->next())
 * {
 *   echo $source['column_name'];
 * }
 * </code>
 *
 * This interface is very flexible because you don't depend on the implementation
 * of the data source. It does not matter whether the data is stored in Propel
 * objects, in arrays or in strings.
 *
 * In most cases, however, you will want to work directly with the underlying
 * data types (e.g. Propel objects). This is why the method current() used by
 * the iterator always returns the current row in the original format.
 *
 * <code>
 * $source = new ObjectDataSource();
 * foreach ($source as $object)
 * {
 *   $object->callMethod();
 * }
 *
 * $source = new ArrayDataSource();
 * foreach ($source as $array)
 * {
 *   echo $array['key'];
 * }
 * </code>
 *
 * You can use the remaining methods of this interface to preprocess the data
 * before iterating it. You can call setSort() to sort the data, setOffset()
 * to skip a given amount of rows or setLimit() to tell the iterator to stop
 * after processing a given amount of rows. Calling setOffset() and setLimit()
 * before iterating may, depending on the implementation, result in a greater
 * processing performance (f.i. LIMIT and OFFSET clauses in SQL data sources).
 *
 * @package    symfony
 * @subpackage grid
 * @author     Bernhard Schussek <bschussek@gmail.com>
 * @version    SVN: $Id$
 */
interface sfDataSourceInterface extends SeekableIterator, ArrayAccess, Countable, sfDataSourceFilterableInterface
{
  const ASC  = 'asc';
  const DESC = 'desc';

  /** Comparison type. */
  const EQUAL = "=";

  /** Comparison type. */
  const NOT_EQUAL = "<>";

  /** Comparison type. */
  const GREATER_THAN = ">";

  /** Comparison type. */
  const LESS_THAN = "<";

  /** Comparison type. */
  const GREATER_EQUAL = ">=";

  /** Comparison type. */
  const LESS_EQUAL = "<=";

  /** Comparison type. */
  const LIKE = " LIKE ";

  /** Comparison type. */
  const NOT_LIKE = " NOT LIKE ";

  /**
   * Sorts the data source by the given column in the given order.
   *
   * @param  string $column The name of the column by which to sort
   * @param  string $order  The order in which to sort. Must be one of
   *                        sfDataSourceInterface::ASC and sfDataSourceInterface::DESC
   */
  public function setSort($column, $order = sfDataSourceInterface::ASC);

  /**
   * Sets the number of rows to skip in the beginning of the data source when
   * iterating. If set to 0, no rows are skipped.
   *
   * Depending on the implementation of the data source, calling setOffset()
   * before iterating may increase processing performance.
   *
   * @param integer $offset The number of rows to skip
   */
  public function setOffset($offset);

  /**
   * Sets the maximum number of rows processed by the iterator. If set to 0
   * (=no limit), all available rows are processed.
   *
   * Depending on the implementation of the data source, calling setLimit()
   * before iterating may increase processing performance.
   *
   * @param integer $limit The maximum number of rows to process
   */
  public function setLimit($limit);

  /**
   * Returns the total number of rows. As opposed to count(), the result of
   * this method is not affected by any limits set on this data source.
   *
   * <code>
   * $source = new DataSourceWith100Items();
   *
   * echo $source->count();    // returns "100"
   * echo $source->countAll(); // returns "100"
   *
   * $source->setLimit(20);
   *
   * echo $source->count();    // returns "20"
   * echo $source->countAll(); // returns "100"
   * </code>
   *
   * @return integer The total number of rows
   */
  public function countAll();

  /**
   * Returns whether the given column an be returned by the data source.
   *
   * @param  string $column  The column name to check for
   * @throws LogicException  Throws an exception if the column cannot be returned by this dataset
   */
  public function requireColumn($column);
}