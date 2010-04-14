<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class sfDataSourceMock extends ArrayIterator implements sfDataSourceInterface
{
  public
    $sortedBy = null,
    $limit = null,
    $offset = null;

  public function __construct($limit = null)
  {
    $data = array(
      array('id' => 1, 'name' => 'Fabien'),
      array('id' => 2, 'name' => 'Francois'),
      array('id' => 3, 'name' => 'Jonathan'),
      array('id' => 4, 'name' => 'Fabian'),
      array('id' => 5, 'name' => 'Kris'),
      array('id' => 6, 'name' => 'Nicolas'),
      array('id' => 7, 'name' => 'Fabian'),
      array('id' => 8, 'name' => 'Dustin'),
      array('id' => 9, 'name' => 'Carl'),
    );

    if (!is_null($limit))
    {
      $data = array_slice($data, 0, $limit);
    }

    parent::__construct($data);
  }

  public function setSort($column, $order = sfDataSourceInterface::ASC)
  {
    $this->sortedBy = array($column, $order);
  }

  public function setOffset($offset)
  {
    $this->offset = $offset;
  }

  public function setLimit($limit)
  {
    $this->limit = $limit;
  }

  public function count()
  {
    return is_null($this->limit) ? parent::count() : min($this->limit, parent::count());
  }

  public function countAll()
  {
    return parent::count();
  }

  public function offsetGet($key)
  {
    $array = $this->current();
    return $array[$key];
  }

  public function requireColumn($column)
  {
    if (!(in_array($column, array('id', 'name'))))
    {
      throw new LogicException(sprintf('The column "%s" has not been defined in the datasource', $column));
    }

  }

  /**
   * @see sfDataSourceInterface
   */
  public function addFilter($column, $value, $comparison = sfDataSource::EQUAL)
  {
    throw new Exception('This method has not been implemented yet');
  }
}