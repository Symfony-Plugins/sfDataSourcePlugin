<?php 

class Person extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('person');
    $this->hasColumn('id', 'integer', 10, array('primary' => true, 'autoincrement' => true));
    $this->hasColumn('name', 'string', 255);
  }
  
  public function setUp()
  {
  }
}