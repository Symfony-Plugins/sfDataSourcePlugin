<?php

/*
 * This file is part of the symfony package.
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class implements the interface sfDataSourceInterface for accessing
 * data stored in Propel (1.5) tables.
 *
 * You can either pass a model name, an instance of PropelModelQuery 
 * TODO: instance of PropelCollection to the constructor.
 *
 * <code>
 * // fetches all user objects
 * $source = new sfDataSourcePropel('User');
 *
 * // fetches user objects with IDs 1 to 100
 * $q = PropelQuery::from('User')->where('id BETWEEN ? AND ?', array(1, 100));
 * $source = new sfDataSourcePropel($q);
 *
 * // uses the objects in the given collection
 * $coll = PropelQuery::from('User')->find();
 * $source = new sfDataSourcePropel($coll);
 * </code>
 *
 * This class will work the same way no matter how you instantiate it. Most of the
 * time, however, it is better to base the source on a model name or on a
 * PropelModelQuery, because sorting and limiting result sets is more efficient
 * when done by the database than when done by Php.
 *
 * You can iterate the data source like any other data source. If you iterate
 * this class with foreach, the current row will always be an instance of
 * your model.
 *
 * <code>
 * // unified data source iteration
 * $source = new sfDataSourcePropel('User');
 * for ($source->rewind(); $source->valid(); $source->next())
 * {
 *   echo $source['username'];
 * }
 *
 * // iteration with foreach specific to this driver
 * $source = new sfDataSourcePropel('User');
 * foreach ($source as $user)
 * {
 *   echo $user->getUsername(); // $user instanceof User
 * }
 * </code>
 *
 * @package    symfony
 * @subpackage DataSource
 * @author     Leon van der Ree <leon@fun4me.demon.nl>
 * @version    SVN: $Id$
 */
class sfDataSourcePropel extends sfDataSource
{
	/**
	 * 
	 * @var ObjectPathCriteria
	 */
  public
    $query    = null;
    
  /**
   * 
   * @var PropelCollection
   */
  protected
    $data     = null;

  /**
   * Constructor.
   *
   * The dataSourcePropel (1.5) can be given string, as instance of PropelModelQuery or as
   * instance of PropelCollection.  If you pass in a PropelModelQuery, the
   * object will be cloned because it needs to be modified internally.
   *
   * <code>
   * // fetches all user objects
   * $source = new sfDataSourcePropel('User');
   *
   * // fetches user objects with IDs 1 to 100
   * $q = PropelQuery::from('User')->where('id BETWEEN ? AND ?', array(1, 100));
   * $source = new sfDataSourcePropel($q);
   *
   * // uses the objects in the given collection
   * $coll = PropelQuery::from('User')->find();
   * $source = new sfDataSourcePropel($coll);
   * </code>
   *
   * @param  mixed $source             The propel source as described above
   * @throws UnexpectedValueException  Throws an exception if the source is a
   *                                   string, but not an existing class name
   * @throws UnexpectedValueException  Throws an exception if the source is a
   *                                   valid class name that does not inherit
   *                                   Propel_Record
   * @throws InvalidArgumentException  Throws an exception if the source is
   *                                   neither a valid model class name nor an
   *                                   instance of ObjectPathCriteria or
   *                                   PropelCollection.
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
      // that class must be a child of Propel_Record
      if (!$reflection->isSubclassOf('BaseObject')) 
      {
        throw new UnexpectedValueException(sprintf('Class "%s" is no instance of Propel (BaseObject)Model', $source));
      }

      $this->query = PropelQuery::from($source);
    }
    // ...the source can also be passed as query...
    elseif ($source instanceof ObjectPathCriteria) // TODO: make ModelCriteria possible as well! so without ObjectPathBehavior
    {
      $this->query = clone $source;
    }
    // ... and there is support for PropelCollection result-sets (TODO: although not fully implemented yet)
    elseif ($source instanceof PropelCollection)
    {
      $this->data = $source;
    }
    else
    {
      throw new InvalidArgumentException('The source must be an instance of ObjectPathCriteria, PropelCollection or a record class name');
    }
  }

  /**
   * Returns whether the data has already been loaded from the database. Will
   * always return TRUE if this source is based on a PropelCollection.
   *
   * @return boolean Whether the data has already been loaded
   */
  private function isDataLoaded()
  {
    return $this->data !== null;
  }

  /**
   * Loads the data from the database. This method may not be called if this
   * source is based on a PropelCollection.
   */
  private function loadData()
  {
    $this->data = $this->query->find();
  }

  /**
   * Returns the value of the given field of the current record while iterating.
   *
   * @param  string $field The name of the field
   * @return mixed         The value of the given field in the current record
   */
  public function offsetGet($field)
  {
    $accessors = explode('.', $field);
    
    $obj = $this->current();
    foreach ($accessors as $accessor)
    {
      $method = 'get'.$accessor; //TODO: ucfirst? // TODO: move to sfPropelObjectPathBehaviorPlugin?
      $obj = $obj->$method();
    }
    
    return $obj;
  }
  
  public function offsetExists($field)
  {
    $exist = true;
    
    try
    {
      $this->offsetGet($field);
    } 
    // in case accessor-method does not exists PropelException is thrown
    catch (PropelException $e)
    {
      $exist = false;
    }
    
    return $exist;
  }

  /**
   * Returns the current record while iterating. If the internal row pointer does
   * not point at a valid row, an exception is thrown.
   *
   * @return Propel_Record       The current record
   * @throws OutOfBoundsException  Throws an exception if the internal row
   *                               pointer does not point at a valid row.
   */
  public function current()
  {
    if (!$this->isDataLoaded())
    {
      $this->loadData();
    }

    // if this object has been initialized with a PropelCollection, we need
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
   * $source = new sfDataSourcePropel('User');
   * echo $source->count();    // returns "100"
   * $source->setLimit(20);
   * echo $source->count();    // returns "20"
   * </code>
   *
   * @return integer The number of rows
   */
  public function count()
  {
    if ($this->query)
    {
      $count = $this->query->count();
    }
    else
    {
      $count = count($this->data);
    }

    // if this object has been initialized with a PropelCollection, we need
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
    // if this object has not been initialized with a PropelCollection,
    // send a count query
    if ($this->query)
    {
  		$criteria = clone $this->query;
  		// no offset, no limit
  		$criteria->offset(0);
  		$criteria->limit(0);
      
      return $criteria->count();
    }
    else
    {
      return count($this->data);
    }
  }

  /**
   * this method makes sure related objects are joined,
   * however it does not known if the required accessor does exist (for now we see that in runtime)
   *  
   * @param string $column the propertyPath to the column [RelationName.]*ColumnName
   */
  public function requireColumn($column)
  {
    // is you have a query object
    if ($this->query)
    {
      // check if an objectPath has been given
      $lastDot = strrpos($column, '.');
      if ($lastDot !== false)
      {
        // get the objectPath
        $objectPath = substr($column, 0, $lastDot);
        
        // and join accordingly
        $this->query->joinByObjectPath($objectPath);
      }
    }
    // TODO: in no query-object is used
  }

  /**
   * Sets the offset and reloads the data if necessary.
   *
   * @see sfDataSource::setOffset()
   */
  public function setOffset($offset)
  {
    parent::setOffset($offset);

    // if this object has not been initialized with a PropelCollection,
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

    // if this object has not been initialized with a PropelCollection,
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
      throw new RuntimeException('A data source based on a PropelCollection cannot be sorted'); // TODO
    }

    // translate datasource to criteria-sorting
    $order = ($order == sfDataSourceInterface::ASC) ? Criteria::ASC : Criteria::DESC;
    $this->query->orderBy($column, $order);
    $this->refresh();
  }
  
  /**
   * @see sfDataSource::doSort()
   */  
  public function setSort($column, $order = sfDataSourceInterface::ASC)
  {
    // add the Join, if required
    $this->requireColumn($column);
    
    return parent::setSort($column, $order);
  }


  /**
   * @see sfDataSourceInterface
   */
  public function setFilter($fields)
  {
    throw new Exception('This method has not been implemented yet');
  }

}