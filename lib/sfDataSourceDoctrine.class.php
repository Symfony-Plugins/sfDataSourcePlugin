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
 * data stored in Doctrine tables.
 *
 * You can either pass a model name, an instance of Doctrine_Query or an
 * instance of Doctrine_Collection to the constructor.
 *
 * <code>
 * // fetches all user objects
 * $source = new sfDataSourceDoctrine('User');
 *
 * // fetches user objects with IDs 1 to 100
 * $q = Doctrine_Query::create()->from('User')->where('id BETWEEN ? AND ?', array(1, 100));
 * $source = new sfDataSourceDoctrine($q);
 *
 * // uses the objects in the given collection
 * $coll = Doctrine::getTable('User')->findByGender('m');
 * $source = new sfDataSourceDoctrine($coll);
 * </code>
 *
 * This class will work the same way no matter how you instantiate it. Most of the
 * time, however, it is better to base the source on a model name or on a
 * Doctrine query, because sorting and limiting result sets is more efficient
 * when done by the database than when done by PHP.
 *
 * You can iterate the data source like any other data source. If you iterate
 * this class with foreach, the current row will always be an instance of
 * your model.
 *
 * <code>
 * // unified data source iteration
 * $source = new sfDataSourceDoctrine('User');
 * for ($source->rewind(); $source->valid(); $source->next())
 * {
 *   echo $source['username'];
 * }
 *
 * // iteration with foreach specific to this driver
 * $source = new sfDataSourceDoctrine('User');
 * foreach ($source as $user)
 * {
 *   echo $user->username; // $user instanceof User
 * }
 * </code>
 *
 * @package    symfony
 * @subpackage grid
 * @author     Bernhard Schussek <bschussek@gmail.com>
 * @version    SVN: $Id$
 */
class sfDataSourceDoctrine extends sfDataSource
{
  protected
  $query    = null,
  $data     = null;

  /**
   * Constructor.
   *
   * The data source can be given as array, as instance of Doctrine_Query or as
   * instance of Doctrine_Collection.  If you pass in a Doctrine_Query, the
   * object will be cloned because it needs to be modified internally.
   *
   * <code>
   * // fetches all user objects
   * $source = new sfDataSourceDoctrine('User');
   *
   * // fetches user objects with IDs 1 to 100
   * $q = Doctrine_Query::create()->from('User')->where('id BETWEEN ? AND ?', array(1, 100));
   * $source = new sfDataSourceDoctrine($q);
   *
   * // uses the objects in the given collection
   * $coll = Doctrine::getTable('User')->findByGender('m');
   * $source = new sfDataSourceDoctrine($coll);
   * </code>
   *
   * @param  mixed $source             The data source
   * @throws UnexpectedValueException  Throws an exception if the source is a
   *                                   string, but not an existing class name
   * @throws UnexpectedValueException  Throws an exception if the source is a
   *                                   valid class name that does not inherit
   *                                   Doctrine_Record
   * @throws InvalidArgumentException  Throws an exception if the source is
   *                                   neither a valid model class name nor an
   *                                   instance of Doctrine_Query or
   *                                   Doctrine_Collection.
   */
  public function __construct($source)
  {
    // the source can be passed as model class name...
    if (is_string($source))
    {
      // then it must be an existing class
      if (!class_exists($source))
      {
        throw new UnexpectedValueException(sprintf('Class "%s" does not exist', $source));
      }
      $reflection = new ReflectionClass($source);
      // that class must be a child of Doctrine_Record
      if (!$reflection->isSubclassOf('Doctrine_Record'))
      {
        throw new UnexpectedValueException(sprintf('Class "%s" is no Doctrine record class', $source));
      }

      $this->query = Doctrine_Query::create()->from($source);
    }
    // ...the source can also be passed as query...
    elseif ($source instanceof Doctrine_Query)
    {
      $this->query = clone $source;
    }
    // ...or as collection
    elseif ($source instanceof Doctrine_Collection)
    {
      $this->data = $source;
    }
    else
    {
      throw new InvalidArgumentException('The source must be an instance of Doctrine_Query, Doctrine_Collection or a record class name');
    }
  }

  /**
   * Returns whether the data has already been loaded from the database. Will
   * always return TRUE if this source is based on a Doctrine collection.
   *
   * @return boolean Whether the data has already been loaded
   */
  private function isDataLoaded()
  {
    return $this->data !== null;
  }

  /**
   * Loads the data from the database. This method may not be called if this
   * source is based on a Doctrine collection.
   */
  private function loadData()
  {
    $this->data = $this->query->execute();
  }

  /**
   * Returns the value of the given field of the current record while iterating.
   *
   * @param  string $field The name of the field
   * @return mixed         The value of the given field in the current record
   */
  public function offsetGet($field)
  {
    $obj = $this->current();
    $accessors = explode('.', $field);

    foreach ($accessors as $accessor)
    {
      $method = 'get'.$accessor; //TODO: maybe move to ObjectPath Plugin? object->getValueByPropertyPath($field)...
      $obj = $obj->$method();
    }

    return $obj;
  }

  /**
   * Returns the current record while iterating. If the internal row pointer does
   * not point at a valid row, an exception is thrown.
   *
   * @return Doctrine_Record       The current record
   * @throws OutOfBoundsException  Throws an exception if the internal row
   *                               pointer does not point at a valid row.
   */
  public function current()
  {
    if (!$this->isDataLoaded())
    {
      $this->loadData();
    }

    // if this object has been initialized with a Doctrine_Collection, we need
    // to add the offset while retrieving objects
    $offset = $this->query ? 0 : $this->getOffset();

    if (!$this->valid())
    {
      throw new OutOfBoundsException(sprintf('The result with index %s does not exist', $this->key()));
    }

    return $this->data[$this->key()+$offset];
  }

  /**
   * Returns the number of records in the data source. If a limit is set with
   * setLimit(), the maximum return value is that limit. You can use the method
   * countAll() to count the total number of rows regardless of the limit.
   *
   * <code>
   * $source = new sfDataSourceDoctrine('User');
   * echo $source->count();    // returns "100"
   * $source->setLimit(20);
   * echo $source->count();    // returns "20"
   * </code>
   *
   * @return integer The number of rows
   */
  public function count()
  {
    if (!$this->isDataLoaded())
    {
      $this->loadData();
    }

    $count = count($this->data);

    // if this object has been initialized with a Doctrine_Collection, we need
    // to subtract the offset from the object count manually
    if (!$this->query)
    {
      $count -= $this->getOffset();
    }

    return $this->getLimit()==0 ? $count : min($count, $this->getLimit());
  }

  /**
   * @see sfDataSourceInterface::countAll()
   */
  public function countAll()
  {
    // if this object has not been initialized with a Doctrine_Collection,
    // send a count query
    if ($this->query)
    {
      return $this->query->count();
    }
    else
    {
      return count($this->data);
    }
  }

  /**
   * @see sfDataSourceInterface::requireColumn()
   */
  public function requireColumn($column)
  {
    // check if an objectPath has been given
    $lastDot = strrpos($column, '.');
    if ($lastDot !== false)
    {
      // get the objectPath
      $objectPath = substr($column, 0, $lastDot);

      // and join accordingly
      $this->query->joinByObjectPath($objectPath);

      //TODO: if we don't have sfAlyssaDoctrineObjectPathPlugin installed?
    }
    // check if the given column is valid
    elseif(!$this->getTable()->hasColumn($column))
    {
      throw new LogicException(sprintf('The column "%s" has not been defined in the datasource', $column));
    }
  }

  /**
   * Sets the offset and reloads the data if necessary.
   *
   * @see sfDataSource::setOffset()
   */
  public function setOffset($offset)
  {
    parent::setOffset($offset);

    // if this object has not been initialized with a Doctrine_Collection,
    // update the query
    if ($this->query)
    {
      $this->query->offset($offset);
      $this->refresh();
    }
  }

  /**
   * Sets the limit and reloads the data if necessary.
   *
   * @see sfDataSource::setLimit()
   */
  public function setLimit($limit)
  {
    parent::setLimit($limit);

    // if this object has not been initialized with a Doctrine_Collection,
    // update the query
    if ($this->query)
    {
      $this->query->limit($limit);
      $this->refresh();
    }
  }

  /**
   * Reloads the data from the database, if the data had already been loaded.
   * Calling this method is essential when updating the internal query.
   */
  public function refresh()
  {
    if ($this->isDataLoaded())
    {
      $this->loadData();
    }
  }

  /**
   * @see sfDataSource::doSort()
   */
  protected function doSort($column, $order)
  {
    if (!$this->query)
    {
      throw new RuntimeException('A data source based on a Doctrine_Collection cannot be sorted');
    }

    $this->query->orderByProperyPath($column.' '.strtoupper($order));
    $this->refresh();
  }

  /**
   * @see sfDataSourceInterface
   */
  public function addFilter($column, $value, $comparison = sfDataSource::EQUAL)
  {
    $this->requireColumn($column);

    $expr = $column.' '.$comparison.' ?';
    return $this->query->addWhereProperyPath($expr, $value);
  }

  /**
   * Returns the Doctrine table associated with the model of this source.
   *
   * @return Doctrine_Table The Doctrine table
   */
  public function getTable()
  {
    if ($this->query)
    {
      // we need to know the base table of the query
      // ...may be there are aliases and another elements,
      // only the first position we need for
      $from = $this->query->getDqlPart('from');
      $from = $from[0];
      $length = strpos($from, ' ');
      // if we have an alias
      if ($length !== false)
        $from = substr($from, 0, $length);

      return Doctrine::getTable($from);
    }
    else
    {
      return $this->data->getTable();
    }
  }
}
