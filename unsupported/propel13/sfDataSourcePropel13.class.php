<?php

/*
 * This file is part of the symfony package.
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 * (c) Frans van der Lek
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class implements the interface sfDataSourceInterface for accessing
 * data stored in Propel tables.
 *
 * <code>
 * // fetches all user objects
 * $source = new sfDataSourcePropel13('User');
 * // this will return a hydrated iterator with User Objects (using doSelect)
 *
 * // fetches user objects from Criteria
 * $selectCriteria = new Criteria();
 * $countCriteria = new Criteria();
 * UserPeer::addSelectColumns($criteria);
 *
 * $source = new sfDataSourcePropel13($selectCriteria, $countCriteria);
 * // this source will contain non-hydrated resultsets,
 * // Column must be tablename.COLUMNNAME syntax (from propel)
 *
 * </code>
 *
 * You can iterate the data source like any other data source. It will always
 * provide an array of columns.
 *
 * <code>
 * // unified data source iteration
 * $source = new sfDataSourcePropel13('User');
 * for ($source->rewind(); $source->valid(); $source->next())
 * {
 *   echo $source['username'];
 * }
 *
 * // iteration with foreach specific to this driver
 * $source = new sfDataSourcePropel13('User');
 * foreach ($source as $user)
 * {
 *   echo $source['username'];
 * }
 *
 * // the $user object will be an instantiated User-object or a raw resultset
 * // depending on the dataSourcePropel-constructor
 *
 * </code>
 *
 * @package    symfony
 * @subpackage grid
 * @author     Leon van der Ree <leon@fun4me.demon.nl>
 * @version    SVN: $Id$
 */
class sfDataSourcePropel13 extends sfDataSource
{

  /**
   * Data contains references to the hydrated results, or in case of custom criteria: raw values in associative arrays
   *
   * @var array    data
   */
  protected $data     = null;

  /**
   * Database Connection, making it possible to use multiple connections in your applicationx
   *
   * @var PropelPDO
   */
  protected $connection = null;

  /**
   * The name of the base Class (an object from the data model)
   *
   * @var string
   */
  protected $baseClass = null;

  /**
   * holder of all objectPaths
   *
   * @var array
   */
  protected $objectPaths = array();

  /**
   * Enter description here...
   *
   * @var Criteria
   */
  protected $selectCriteria = null;
  /**
   * Enter description here...
   *
   * @var Criteria
   */
  protected $countCriteria = null;

  /**
   * Constructor.
   *
   * The data source can be constructed from className
   * or a custom Criteria object. Custom criteria objects will not get hydrated.
   *
   * the Criteria object will be cloned, since it will be modified internally.
   *
   * <code>
   * // fetches all user objects
   * $source = new sfDataSourcePropel13('User');
   * // this will return a hydrated iterator with User Objects
   *
   * // fetches user objects from Criteria
   * $selectCriteria = new Criteria();
   * $countCriteria = new Criteria();
   * UserPeer::addSelectColumns($criteria);
   *
   * $source = new sfDataSourcePropel13($selectCriteria, $countCriteria);
   * // this source will contain non-hydrated resultsets,
   * // propertyPath must be tablename.COLUMNNAME syntax (from propel)
   * </code>
   *
   * @param  mixed $classNameOrSelectCriteria        The data source (a select Criteria, or an
   *                                                 (array of) object Path(s)
   * @param Criteria $criteriaOrCountCriteria        initial criteria (obitional) or requered CountCriteria
   *                                                 depending on className or Select Criteria
   *
   * @throws UnexpectedValueException  Throws an exception if the class does not exist
   *                                   or if the select source is a Criteria, but a count Criteria is missing
   * @throws InvalidArgumentException  Throws an exception if the source is
   *                                   neither a valid propel model class name
   *                                   nor a Criteria.
   */
  public function __construct($classNameOrSelectCriteria, Criteria $criteriaOrCountCriteria = null)
  {
    // if the source is provided as object paths, create hydratable criteria
    if (is_string($classNameOrSelectCriteria))
    {
      $this->baseClass = $classNameOrSelectCriteria;

      // check if Class exist
      if (!class_exists($this->baseClass))
      {
        throw new UnexpectedValueException(sprintf('Class "%s" does not exist', $this->baseClass));
      }

      // add base class to array of Object Paths
      $this->addObjectPath($this->baseClass);

      if ($criteriaOrCountCriteria != null)
      {
        $this->selectCriteria = clone $criteriaOrCountCriteria;
      }
      else
      {
        $this->selectCriteria = new Criteria();
      }
    }
    // ...the source can also be passed as custom criteria, these will not be hydrated!
    elseif ($classNameOrSelectCriteria instanceof Criteria)
    {
      if (!$criteriaOrCountCriteria instanceof Criteria)
      {
        throw new UnexpectedValueException(sprintf('The CountCriteria argument is required when providing a Criteria object as source. The provided $criteriaOrCountCriteria argument is not an instance of Criteria'));
      }

      $this->selectCriteria = clone $classNameOrSelectCriteria;
      $this->countCriteria = clone $criteriaOrCountCriteria;
    }
    else
    {
      throw new InvalidArgumentException('The source must be an instance of Criteria or a propel class name');
    }

    $this->init();
  }

  /**
   * Customisable init function
   *
   * this method is defined to make extending this class easy.
   *
   */
  protected function init()
  {
    //add your custom init here
  }

  /**
   * Returns whether the data has already been loaded from the database.
   *
   * @return boolean Whether the data has already been loaded
   */
  protected function isDataLoaded()
  {
    return $this->data !== null;
  }

  /**
   * protected method to load the Data,
   *
   * the functionality depends on the constructor call (have custom Criteria been provided, or is hydration of objects possible)
   */
  protected function loadData()
  {
    // data holds all main results
    $this->data = array();

    // hydrate objects in case object paths have been defined
    if ($this->baseClass != null)
    {
      sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));

      // we're going to modify criteria, so copy it first
      $criteria = clone $this->selectCriteria;

      $criteria = addJoinsAndSelectColumns($criteria, $this->objectPaths);
      $this->data = hydrate($criteria, $this->objectPaths, $this->connection);
    }
    // or return raw result sets in case custom criteria objects have been provided
    else
    {
      $stmt = BasePeer::doSelect($this->selectCriteria, $this->connection);

      $results = $stmt->fetchAll(PDO::FETCH_NUM);

      $selectColumns = array_merge($this->selectCriteria->getSelectColumns(), 
                                   array_keys($this->selectCriteria->getAsColumns()));
      
      foreach ($results as $result)
      {
        $row = array();
        foreach ($result as $key => $field)
        {
          // translate columnnames
          $row[$selectColumns[$key]] = $field;
        }
        $this->data[] = $row;
      }

      $stmt->closeCursor();
    }
  }

  /**
   * sets the connection to the database,
   *
   * default connection is null to resolve the standard connection automatically
   *
   * @param PropelPDO $connection
   */
  public function setConnection($connection)
  {
    $this->connection = $connection;
  }


  /**
   * Returns the value of the given field of the current record while iterating.
   *
   * @param  string $field The name of the field
   * @return mixed         The value of the given field in the current record
   */
  public function offsetGet($field)
  {
    $current = $this->current();

    // in case of hydrated objects, use getters
    if ($this->baseClass != null)
    {
      $result = $current;

      // TODO: check for object property or custom column, see below
      $getters = $field.'.';
      while (strlen($getters) > 0)
      {
        list($getter, $getters) = explode('.', $getters, 2);
        if (isset($result))
        {
          $result = call_user_func(array($result, 'get'.$getter));
          // TODO: HACK for one-to-many currenlty the first related gets returned, not the array, and no iteration over the items in the array
          // The array can be accessed by the object->getProperty accessor, but not by this dataSource-arrayAccess instance
          if (is_array($result))
          {
            $result = array_shift($result);
          }
        }
        else
        {
          // return null, since left-join didn't retreived a related object
          return null;
        }
      }
      //TODO: custom column: hydration needs to be implemented for related custom columns (you should define the custom columns in your peer-classes)
      //return $result->getCustomColumnValue($field);

    }
    // else in case of custom selectColumns, return column-values directly
    else
    {
      $result = $current[$field];
    }

    return $result;
  }

  /**
   * Returns the current record while iterating. If the internal row pointer does
   * not point at a valid row, an exception is thrown.
   *
   * @return array                 The current row
   * @throws OutOfBoundsException  Throws an exception if the internal row
   *                               pointer does not point at a valid row.
   */
  public function current()
  {
    if (!$this->isDataLoaded())
    {
      $this->loadData();
    }

    if (!$this->valid())
    {
      throw new OutOfBoundsException(sprintf('The result with index %s does not exist', $this->key()));
    }

    return $this->data[$this->key()];
  }

  /**
   * Returns the number of records in the data source. If a limit is set with
   * setLimit(), the maximum return value is that limit. You can use the method
   * countAll() to count the total number of rows regardless of the limit.
   *
   * <code>
   * $source = new sfDataSourcePropel13('User');
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

    return count($this->data);
  }

  /**
   * @see sfDataSourceInterface::countAll()
   */
  public function countAll()
  {
    // in case we are using a peer class
    if ($this->baseClass != null)
    {
      sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));

      $count = countAll($this->selectCriteria, $this->objectPaths, $this->connection);
    }
    // or in case we are using custom criteria objects for select and count
    else
    {
      $criteria = clone $this->countCriteria;

      $criteria->clearOrderByColumns(); // ORDER BY won't ever affect the count
      $criteria->setLimit(-1);          // LIMIT obviously affects the count
      $criteria->setOffset(0);          // OFFSET affects the count negative

      if (!$criteria->hasSelectClause()) {
        throw new Exception('Please provide some select columns in the countCriteria. This can be a subset of the columns in the select-criteria.');
      }

      // BasePeer returns a PDOStatement
      $stmt = BasePeer::doCount($criteria, $this->connection);

      if ($row = $stmt->fetch(PDO::FETCH_NUM))
      {
        $count = (int) $row[0];
      }
      else
      {
        $count = 0; // no rows returned; we infer that means 0 matches.
      }
      $stmt->closeCursor();
    }

    return $count;
  }

  public function addObjectPath($objectPath)
  {
    if (!in_array($objectPath, $this->objectPaths))
    {
      sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));
      checkObjectPath($objectPath);

      // add valid objectPath to array
      $this->objectPaths[] = $objectPath;
    }
  }

  /**
   * @see sfDataSourceInterface::addPropertyPath()
   */
  public function requireColumn($column)
  {
    if ($this->baseClass != null)
    {
      sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));

      // throws an exception if the property cannot be resolved
      checkPropertyPath($this->baseClass , $column);

      $objectPath = getObjectPathFromProperyPath($this->baseClass , $column);

      $this->addObjectPath($objectPath);
    }
    else
    {
      if (!in_array($column, $this->selectCriteria->getSelectColumns()) && !array_key_exists($column, $this->selectCriteria->getAsColumns()))
      {
        throw new LogicException(sprintf('The column "%s" has not been defined in the datasource. The following columns are known: (%s)', 
                                         $column,
                                         implode(', ', $this->selectCriteria->getSelectColumns())));
      }
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

    $this->selectCriteria->setOffset($offset);
    $this->refresh();
  }

  /**
   * Sets the limit and reloads the data if necessary.
   *
   * @see sfDataSource::setLimit()
   */
  public function setLimit($limit)
  {
    parent::setLimit($limit);

    $this->selectCriteria->setLimit($limit);
    $this->refresh();
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
   * To be implemented in an extension
   *
   * return null to disable the default sorting
   *
   * @param string $column
   * @param string $order
   * @return string     the column name to do the default sorting on.
   */
  protected function doCustomSort($column, $order)
  {
    return $column;
  }

  /**
   * @see sfDataSource::doSort()
   */
  protected function doSort($column, $order)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));

    // translate $column to propel column-name
    if ($this->baseClass)
    {
      $column = translatePropertyPathToAliasedColumn($this->baseClass, $column);
    }
    $column = $this->doCustomSort($column, $order);

    if ($column != null)
    {
      $this->selectCriteria->clearOrderByColumns();
      switch ($order)
      {
        case sfDataSourceInterface::ASC:
          $this->selectCriteria->addAscendingOrderByColumn($column);
          break;
        case sfDataSourceInterface::DESC:
          $this->selectCriteria->addDescendingOrderByColumn($column);
          break;
        default:
          throw new Exception('sfDataSourcePropel13::doSort() only accepts "'.sfDataSourceInterface::ASC.'" or "'.sfDataSourceInterface::DESC.'" as argument');
      }
    }

    $this->refresh();
  }

  /**
   * @see sfDataSourceInterface
   */
  public function setFilter($fields)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));

    foreach ($fields as $propertyPath => $column)
    {
      $this->addFilter($propertyPath, $column);
    }
  }
  
  /**
   * add one constraint to the criteria
   *
   * @param string $propertyPath
   * @param array $column
   */
  protected function addFilter($propertyPath, $column)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfPropelPropertyPath'));

    $this->requireColumn($propertyPath);
        
    if ($this->baseClass)
    {
      $columnName = translatePropertyPathToAliasedColumn($this->baseClass, $propertyPath);
    }
    else 
    {
      $columnName = $propertyPath;
    }
    
    if (!isset($column['value']))
    {
      throw new Exception("key 'value' not set for filter on column ".$columnName);
    }

    $value = $column['value'];
    // TODO translate all sfDatasourceOperators to Propel Criteria operators
    $operator =  isset($column['operator']) ? $column['operator'] : Criteria::EQUAL;

    $this->selectCriteria->add($columnName, $value, $operator);
    //also filter countCriteria in case we are using raw criteria objects 
    if (!$this->baseClass)
    {
      $this->countCriteria->add($columnName, $value, $operator);
    }
  }

}