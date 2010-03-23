<?php
/**
 * Class to store an attachment
 *
 * @author    Iwan van Staveren (iwan@e-onesw.nl)
 * @author    Leon van der Ree (leon@fun4me.demon.nl)
 * @version   $Id:$
*/
class baseDSImapAttachement
{
  /**
   * The name of the attachment
   *
   * @var string
   */
  protected $filename = '';

  /**
   * Mime type of the attachment
   *
   * @var string
   */
  protected $mimeType = '';
    
  /**
   * The data of the attachment
   *
   * @var mixed
   */
  protected $data = null;
  
  /**
   * The size of the attachement in bytes
   *
   * @var int
   */
  protected $size = 0;
  
  public function __construct($filename, $mimeType, $data, $size = 0)
  {
    $this->filename = $filename;
    $this->mimeType = $mimeType;
    $this->data     = $data;
    if ($size)
    {
      $this->size   = $size;
    }
    else
    {
      $this->size = strlen($this->data);
    }
  }
  
  public function getFilename()
  {
    return $this->filename;
  }
  
  public function getMimeType()
  {
    return $this->mimeType;
  }
  
  public function getData()
  {
    return $this->data;
  }
  
  /**
   * Returns the size of the attachement in bytes
   *
   * @return int
   * 
   */
  public function getSize()
  {
    return $this->size;
  }
  
}
?>