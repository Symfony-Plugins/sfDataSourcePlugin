<?php
/*
 * This file is part of the symfony package.
 * (c) Leon van der Ree <leon@fun4me.demon.nl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This interface allows filtering of dataSources
 * @package    symfony
 * @subpackage grid
 * @author     Leon van der Ree <Leon@fun4me.demon.nl>
 * @version    SVN: $Id$
 */
interface sfDataSourceFilterableInterface
{
  /**
   * 
   * An associative array of field-names with an associative array of value/operator-pairs
   * array('fields' => array(field => array('value' => $value, 'operator' => $operator))
   *       'group_operator => 'AND')
   *
   * Group operator tell what criteria is used to join the filtered fields. Only the follow
   * options can be used: sfDataSource::GROUP_AND, sfDataSource::GROUP_ANY.
   *
   * field names can match column-names but the implementation is up to you.
   *
   * @param array[array[string, string]] $columns
   */
  public function addFilter($column, $value, $comparison = sfDataSource::EQUAL, $group_operator = sfDataSource::GROUP_AND);

}
