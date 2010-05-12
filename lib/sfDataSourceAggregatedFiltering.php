<?php
/*
 * This file is part of the symfony package.
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class aggregates filtering over dataSources
 * @package    symfony
 * @subpackage grid
 * @author     Leon van der Ree <Leon@fun4me.demon.nl>
 * @version    SVN: $Id$
 */
class sfDataSourceAggregatedFiltering implements sfDataSourceFilterableInterface
{
  protected $dataSources = array();
  
  /**
   * 
   * @param array[sfDataSourceAggregatedFiltering] $dataSources
   */
  public function __construct($dataSources = array())
  {
    foreach ($dataSources as $dataSource)
    {
      $this->addDataSource($dataSource);
    }
  }
  
  /**
   * add a dataSource to the list of sources to be filtered
   * 
   * @param sfDataSourceAggregatedFiltering $dataSource
   */
  public function addDataSource(sfDataSourceFilterableInterface $dataSource)
  {
    $this->dataSources[] = $dataSource;
  }
  
  /**
   * @return sfDataSourceAggregatedFiltering
   */
  public function getDataSources()
  {
    return $this->dataSources;
  } 
  
  /**
   * @see sfDataSourceFilterableInterface::addFilter
   */
  public function addFilter($column, $value, $comparison = sfDataSource::EQUAL)
  {
    foreach ($this->dataSources as $dataSource)
    {
      $dataSource->addFilter($column, $value, $comparison);
    }
  }
  
}