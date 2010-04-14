<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class implements the interface sfDataSourceInterface for accessing
 * data stored in arrays.
 *
 * You can use this class if you want to pass an array to a method that requires
 * instances of sfDataSourceInterface. All you need to do is passing the array
 * to the constructor of sfDataSourceArray.
 *
 * <code>
 * $source = new sfDataSourceArray($array);
 * </code>
 *
 * It is important that the given array is formatted correctly. The array
 * must be two dimensional (an array of arrays), where the subordinate arrays
 * represent data rows. All subordinate arrays must have the same length and
 * the same key names, otherwise an exception will be thrown.
 *
 * <code>
 * // valid array
 * $array = new array(
 *   array('id' => 1, 'name' => 'Fabien'),
 *   array('id' => 2, 'name' => 'Kris'),
 * );
 *
 * // invalid array
 * $array = new array(
 *   array('id' => 1, 'name' => 'Fabien'),
 *   array(0 => 2, 'name' => 'Kris'),
 * );
 * </code>
 *
 * @package    symfony
 * @subpackage grid
 * @author     Bernhard Schussek <bschussek@gmail.com>
 * @version    SVN: $Id$
 */
class sfDataSourceArray extends sfDataSource
{
  protected
    $data = array(),
    $originalData = array();

  protected
    $sortColumn = null,
    $sortOrder = null;

  /**
   * Constructor.
   *
   * The given data must be a two dimensional array (an array of arrays), where
   * the subordinate arrays represent data rows. All subordinate arrays must have
   * the same length and the same key names, otherwise an exception will be thrown.
   *
   * <code>
   * // valid array
   * $array = new array(
   *   array('id' => 1, 'name' => 'Fabien'),
   *   array('id' => 2, 'name' => 'Kris'),
   * );
   *
   * // invalid array
   * $array = new array(
   *   array('id' => 1, 'name' => 'Fabien'),
   *   array(0 => 2, 'name' => 'Kris'),
   * );
   * </code>
   *
   * @param  array $data               An array of arrays containing the data
   * @throws InvalidArgumentException  Throws an exception if the given array
   *                                   is not formatted correctly
   */
  public function __construct(array $data)
  {
    if (count($data) > 0)
    {
      $keys = array();

      // compare the keys of the first row with those of all rows
      foreach ($data as $row)
      {
        if (!is_array($row))
        {
          throw new InvalidArgumentException('All rows in the source array must be arrays');
        }
        // extract the keys of the first row
        if (empty($keys))
        {
          $keys = array_keys($row);
        }
        elseif ($keys != array_keys($row))
        {
          throw new InvalidArgumentException('All rows in the source array must have the same keys');
        }
      }
    }

    $this->originalData = $data;
    $this->data = $this->originalData;
    
  }

  /**
   * Returns the current row while iterating. If the internal row pointer does
   * not point at a valid row, an exception is thrown.
   *
   * @return array                 The current row data
   * @throws OutOfBoundsException  Throws an exception if the internal row
   *                               pointer does not point at a valid row.
   */
  public function current()
  {
    if (!$this->valid())
    {
      throw new OutOfBoundsException(sprintf('The result with index %s does not exist', $this->key()));
    }

    return $this->data[$this->key()+$this->getOffset()];
  }

  /**
   * Returns the value of the given column in the current row returned by current()
   *
   * @param  string $column The name of the column
   * @return mixed          The value in the given column of the current row
   */
  public function offsetGet($column)
  {
    $row = $this->current();

    return $row[$column];
  }

  /**
   * Returns the number of records in the data source. If a limit is set with
   * setLimit(), the maximum return value is that limit. You can use the method
   * countAll() to count the total number of rows regardless of the limit.
   *
   * <code>
   * $source = new sfDataSourceArray($arrayWith100Items);
   * echo $source->count();    // returns "100"
   * $source->setLimit(20);
   * echo $source->count();    // returns "20"
   * </code>
   *
   * @return integer The number of rows
   */
  public function count()
  {
    $count = count($this->data) - $this->getOffset();

    return $this->getLimit()==0 ? $count : min($this->getLimit(), $count);
  }

  /**
   * @see sfDataSourceInterface::countAll()
   */
  public function countAll()
  {
    return count($this->data);
  }

  /**
   * requireColumn checks if the source contains the desired column.
   * If the source is an empty array, any column will be accepted, since 
   * the DataSource doesn't have any model-data to base its decision on
   * 
   * @see sfDataSourceInterface::requireColumn()
   */
  public function requireColumn($column)
  {
    if (($this->count() != 0 && !array_key_exists($column, current($this->data))))
    {
      throw new LogicException(sprintf('The column "%s" has not been defined in the datasource', $column));
    }
  }

  /**
   * @see sfDataSource::doSort()
   */
  protected function doSort($column, $order)
  {
    $this->sortColumn = $column;
    $this->sortOrder = $order;

    usort($this->data, array($this, 'sortCallback'));
  }
  
  /**
   * Callback method used by usort(). Compares two arrays by the current
   * sort column in the given sort order.
   *
   * @param  array $a The first array to compare
   * @param  array $b The second array to compare
   * @return integer  Less than zero if the first argument is less than the second,
   *                  exactly zero if both arguments are equal,
   *                  more than zero if the second argument is less than the first
   */
  protected function sortCallback(array $a, array $b)
  {
    if ($this->sortOrder == sfDataSourceInterface::ASC)
    {
      return strcmp($a[$this->sortColumn], $b[$this->sortColumn]);
    }
    else
    {
      return strcmp($b[$this->sortColumn], $a[$this->sortColumn]);
    }
  }
  
  /**
   * @see sfDataSourceInterface
   */
  public function addFilter($column, $value, $comparison = sfDataSource::EQUAL)
  {
    // TODO: because of this, you should first Filter before you sort!
    // TODO: possibly add sortState (asc,desc, none (per field)), and sort after filtering 
    $this->data = array();
    
    $this->requireColumn($columnName);

    if (!isset($column['value']))
    {
      throw new Exception("key 'value' not set for filter on column ".$columnName);
    }

    $value = $column['value'];

    $this->data = array_filter($this->originalData, array($this, 'filterCallback'));
  }

  //TODO: implement filtering on an array
  protected function filterCallback($row)
  {  
    throw new Exception('This method has not been finished yet');
  }
    
}