<?php
/**
 * class baseDSImapMessage
 * 
 * Storage for a imap message.
 *
 * @author    Iwan van Staveren (iwan@e-onesw.nl)
 * @author    Leon vander Ree (leon@fun4me.demon.nl)
 * @version   Release: @package_version@
 */
class baseDSImapMessage
{
  const TYPE_PLAIN = 'TEXT/PLAIN';
  const TYPE_HTML  = 'TEXT/HTML';
  
  const FLAG_SEEN     = '\Seen';
  const FLAG_ASNWERED = '\Answered';
  const FLAG_FLAGGED  = '\Flagged';
  const FLAG_DELETED  = '\Deleted';
  const FLAG_DRAFT    = '\Draft';
  
  const ENCODING_7BIT             = 0;
  const ENCODING_8BIT             = 1;
  const ENCODING_BINARY           = 2;
  const ENCODING_BASE64           = 3;
  const ENCODING_QUOTED_PRINTABLE = 4;
  const ENCODING_OTHER            = 5;
  
  const MIME_TEXT_ID        = 0; const MIME_TEXT         = "TEXT";
  const MIME_MULTIPART_ID   = 1; const MIME_MULTIPART    = "MULTIPART";
  const MIME_MESSAGE_ID     = 2; const MIME_MESSAGE      = "MESSAGE";
  const MIME_APPLICATION_ID = 3; const MIME_APPLICATION  = "APPLICATION";
  const MIME_AUDIO_ID       = 4; const MIME_AUDIO        = "AUDIO";
  const MIME_IMAGE_ID       = 5; const MIME_IMAGE        = "IMAGE";
  const MIME_VIDEO_ID       = 6; const MIME_VIDEO        = "VIDEO";
  const MIME_OTHER_ID       = 7; const MIME_OTHER        = "OTHER";
  
  public static function getMimeType($structure) 
  {
   $primary_mime_type = array(self::MIME_TEXT, self::MIME_MULTIPART, self::MIME_MESSAGE, self::MIME_APPLICATION, self::MIME_AUDIO, self::MIME_IMAGE, self::MIME_VIDEO, self::MIME_OTHER);
   
   if($structure->subtype) 
   {
    return $primary_mime_type[(int) $structure->type] . '/' .$structure->subtype;
   }
   
   return self::TYPE_PLAIN;
 }
  
  
  /**
   * The stream to the imap-server
   *
   * @var resource
   */
  protected $stream;

  /**
   * The subject of the message
   *
   * @var string
   */
  protected $subject;
    
  /**
   * Sender of the message
   *
   * @var string
   */
  protected $from;
  
  /**
   * Receiver(s) of the message
   *
   * @var string
   */
  protected $to;
  
  /**
   * Date and time of the message
   *
   * @var string
   */
  protected $date;
    
  /**
   * The message identifier
   *
   * @var string
   */
  protected $messageId;
  
  /**
   *
   * is a reference to this message id 
   * 
   * @var string
   */
  protected $reference;
  
  /**
   * is a reply to this message id 
   *
   * @var string
   */
  protected $replyTo;
  
  /**
   * size in bytes
   *
   * @var int
   */
  protected $size;
  
  /**
   * UID the message has in the mailbox 
   *
   * @var int
   */
  protected $uid;
  
  /**
   * message sequence number in the mailbox 
   *
   * @var int
   */
  protected $msgno;
  
  /**
   * this message is flagged as recent 
   *
   * @var bool
   */
  protected $recent;
  
  /**
   * this message is flagged 
   *
   * @var bool
   */
  protected $flagged;
  
  /**
   * this message is flagged as answered 
   *
   * @var bool
   */
  protected $answered;
  
  /**
   * this message is flagged for deletion 
   *
   * @var bool
   */
  protected $deleted;
  
  /**
   * this message is flagged as already read 
   *
   * @var bool
   */
  protected $seen;
  
  /**
   *  this message is flagged as being a draft 
   *
   * @var bool
   */
  protected $draft;
  

  /**
   * The structured part of the message 
   *
   * @var unknown_type
   */
  protected $structure;
  
  /**
   * An cached array of attachements
   *
   * @var array
   */  
  protected $attachements;

//  not ready for production use yet
//  /**
//   * An associative array of special headers in the message
//   *
//   * @var array
//   */
//  protected $arrHeaders;
  
  
  //TODO:
  /**
   * CC Receiver(s) of the message
   *
   * @var string
   */
  protected $cc;
  
  //TODO:
  /**
   * BCC Receiver(s) of the message
   *
   * @var string
   */
  protected $bcc;  
  
  
  /**
   * Constructor creating a new DSImapMessage object
   *
   * @param resource $stream  the stream with which the message is retreived (for lazy loading of body and attachements
   * @param string $subject   the messages subject
   * @param string $from      who sent the message
   * @param string $to        recipient
   * @param DateTime $date    when the message was sent
   * @param string $messageId Message-ID
   * @param string $reference is a reference to this message id
   * @param string $replyTo   
   * @param int $size         size in bytes
   * @param int $uid          UID the message has in the mailbox
   * @param int $msgno        message sequence number in the mailbox 
   * @param bool $recent
   * @param bool $flagged
   * @param bool $answered
   * @param bool $deleted
   * @param bool $seen
   * @param bool $draft
   */
  public function __construct($stream,
                              $subject,
                              $from,
                              $to,
                              $date,
                              $messageId,
                              $reference,
                              $replyTo,
                              $size,
                              $uid,
                              $msgno,
                              $recent = false,
                              $flagged = false,
                              $answered = false,
                              $deleted = false,
                              $seen = false,
                              $draft = false)
  {
    $this->stream = $stream;
    
    $this->subject   = $subject;
    $this->from      = $from;
    $this->to        = $to;
    $this->date      = $date;
    $this->messageId = $messageId;
    $this->reference = $reference;
    $this->replyTo   = $replyTo;
    $this->size      = $size;
    $this->uid       = $uid;
    $this->msgno     = $msgno;
    $this->recent     = $recent;
    $this->flagged    = $flagged;
    $this->answered   = $answered;
    $this->deleted    = $deleted;
    $this->seen       = $seen;
    $this->draft      = $draft;
  }

  /**
   * returns the (lazy-loaded) structure of this message 
   *
   * @return object
   */
  protected function getStructure() 
  {
    if (!isset($this->structure))
    {
      $this->structure = imap_fetchstructure($this->stream, $this->uid, FT_UID);
    }
    
    return $this->structure;
  }
  
  protected function createPartArray($structure, $prefix = "")
  {
    $part_array = array();
    
    if (isset($structure->parts))
    {
      foreach ( $structure->parts as $count => $part )
      {
        $this->addPartToArray($part, $prefix . ($count + 1), $part_array);
      }
    }
    
    return $part_array;
  }
  
  protected function addPartToArray($obj, $partno, &$part_array)
  {
    if($obj->type == self::MIME_MESSAGE_ID)
    {
      $this->addPartToArray($obj->parts[0], $partno . ".", $part_array);
    } 
    else
    {
      if (isset($structure->parts))
      {
        foreach ( $obj->parts as $count => $p )
        {
          $this->addPartToArray($p, $partno . "." . ($count + 1), $part_array);
        }
      }
    }
    
    $part_array[] = array('part_number' => $partno, 'part_object' => $obj);
  }
  
  /**
   * Gets an part of the body
   *
   * @param string $mimeType
   * @param object $structure
   * @param string $partNumber
   * @return string part of the body if match found, else false
   */
  protected function getPart($mimeType, $structure = false, $partNumber = false)
  {
    if (!$structure)
    {
      $structure = $this->getStructure();
    }

    if($structure) 
    {
      if($mimeType == self::getMimeType($structure)) 
      {
        if(!$partNumber) 
        {
          $partNumber = "1";
        }
        $text = imap_fetchbody($this->stream, $this->uid, $partNumber, FT_UID | FT_PEEK);
        
        if($structure->encoding == self::ENCODING_BASE64) 
        {
          return imap_base64($text);
        }
        else if($structure->encoding == self::ENCODING_QUOTED_PRINTABLE) 
        {
          return imap_qprint($text);
        }
        else 
        {
          return $text;
        }
      }
   
      // search recursively through multipart
      if($structure->type == self::MIME_MULTIPART_ID) 
      {
        foreach($structure->parts as $index => $subStructure) 
        {
          $prefix = '';
          if($partNumber) 
          {
            $prefix = $partNumber . '.';
          }
          $data = $this->getPart($mimeType, $subStructure, $prefix.($index + 1));
          if($data) 
          {
            return $data;
          }
        }
      }
    }
    
    return false; // TODO: maybe throw exception instead!
  }

  /**
   * Returns the body in HTML, if only plain available this gets converted to HTML
   *
   * @return string
   */
  public function getBodyHtmlElsePlain()
  {
    $body = $this->getBodyHtml();
    
    if (!$body)
    {
      $body = htmlentities($this->getBodyPlain());
    }
    
    return $body;
  }
  
  
  /**
   * Returns the body of this message (in plain)
   *
   * @return string
   */
  public function getBodyPlain()
  {
    return $body = $this->getPart(self::TYPE_PLAIN);
  }

  /**
   * Returns the body of this message (in html)
   *
   * @return string
   */
  public function getBodyHtml()
  {
    return $this->getPart(self::TYPE_HTML);
  }
  
  /**
   * returns an array of hydrated attachements for this message
   *
   * @return array[sfDSImapAttachement]
   */
  public function getAttachments()
  {
    if (!isset($this->attachements))
    {
      $this->attachements = array();
      
      $structure = $this->getStructure();
      $partArray = $this->createPartArray($structure);
      
      //skip first (which should be body)
      array_shift($partArray);
      
      foreach($partArray as $item)
      {
        $key = $item['part_number'];
        $part = $item['part_object'];
        
        $filename = null;
        
        // try to get filename from D-parameters
        if (isset($part->dparameters ))
        {
          foreach ($part->dparameters as $dPar)
          {
            if(strtoupper($dPar->attribute) == "FILENAME" || strtoupper($dPar->attribute) == "NAME")
            {
              if(!empty($dPar->value))
              {
                $filename = $dPar->value;
                $size = $part->bytes;
                break;
              }
            }
          }
        }
        
        // try to get filename from parameters
        if($filename == null)
        {
          foreach ($part->parameters as $par)
          {
            if(strtoupper($par->attribute) == "FILENAME" || strtoupper($par->attribute) == "NAME")
            {
              if(!empty($par->value))
              {
                $filename = $par->value;
//                var_dump($par);
                $size = ceil(($par->bytes/1024));
                break;
              }
            }
          }
        }
            
        // if filename found, fetch body
        if($filename != null)
        {
          $mimeType = self::getMimeType($part);

          $data = imap_fetchbody($this->stream, $this->uid, $key, FT_UID | FT_PEEK);
          if($part->encoding == self::ENCODING_BASE64) 
          {
            $data = imap_base64($data);
          }
          else if($structure->encoding == self::ENCODING_QUOTED_PRINTABLE)
          {
            $data = imap_qprint($data);
          }
          
          $this->attachements[] = new sfDSImapAttachement($filename, $mimeType, $data, $size);
        }
      }
    }
    
    return $this->attachements;
  }
  
  /**
   * the number of attachements for this message
   *
   * @return int the number of attachements for this message
   */
  public function getAttachmentCount()
  {
    return count($this->getAttachments());
  }
  
  /**
   * marks the message as deleted
   *
   */
  public function delete()
  {
    imap_delete($this->stream, $this->uid, FT_UID);
  }

  /**
   * the messages subject 
   *
   * @return string
   */
  public function getSubject()
  {
    return $this->subject;
  }  
  
  /**
   * who sent it 
   *
   * @return string
   */
  public function getFrom()
  {
    return $this->from;
  }
  
  /**
   * recipient 
   *
   * @return string
   */
  public function getTo()
  {
    return $this->to;
  }
  
  /**
   * when was it sent 
   *
   * @return string
   */
  public function getDate($format = 'Y-m-d H:i:s')
  {
    if ($this->date === null) {
      return null;
    }
    
    if ($format == null)
    {
      return $this->date;
    }
    else 
    {
      return $this->date->format($format);
    }
  }
 
  /**
   * Message-ID 
   *
   * @return string
   */
  public function getMessageId()
  {
    return $this->messageId;
  }
  
  /**
   * is a reference to this message id 
   *
   * @return string
   */
  public function getReference() 
  {
    return $this->reference;
  }
  
  /**
   * is a reply to this message id 
   *
   * @return string
   */
  public function getReplyTo() 
  {
    return $this->replyTo;
  }
  
  /**
   * size in bytes 
   *
   * @return int
   */
  public function getSize() 
  {
    return $this->size;
  }
  
  /**
   * UID the message has in the mailbox 
   *
   * @return int
   */
  public function getUid()
  {
    return $this->uid;
  }
  
  /**
   * message sequence number in the mailbox 
   *
   * @return int
   */
  public function getMsgno() 
  {
    return $this->msgno;
  }
  
  /**
   * this message is flagged as recent 
   *
   * @return bool
   */
  public function getRecent() 
  {
    return $this->recent;
  }
  
  /**
   * this message is flagged 
   *
   * @return bool
   */
  public function getFlagged() 
  {
    return $this->flagged;
  }

  /**
   * Mark the message as marked/flagged
   *
   */
  public function setFlagged() 
  {
    imap_setflag_full($this->stream, $this->uid, self::FLAG_FLAGGED, ST_UID);
    $this->flagged = true;
  }

  /**
   * Mark the message as unmarked/unflagged
   *
   */
  public function setUnflagged() 
  {
    imap_clearflag_full($this->stream, $this->uid, self::FLAG_FLAGGED, ST_UID);
    $this->flagged = false;
  }  
  
  /**
   * this message is flagged as answered 
   *
   * @return bool
   */
  public function getAnswered() 
  {
    return $this->answered;
  }
  
  /**
   * this message is flagged for deletion
   *
   * @return bool
   */
  public function getDeleted() 
  {
    return $this->deleted;
  }
  
  /**
   * this message is flagged as already read 
   *
   * @return bool
   */
  public function getSeen() 
  {
    return $this->seen;
  }
  
  /**
   * Mark the message as read/seen
   *
   */
  public function setSeen() 
  {
    imap_setflag_full($this->stream, $this->uid, self::FLAG_SEEN, ST_UID);
    $this->seen = true;
  }

  /**
   * Mark the message as unread/unseen
   *
   */
  public function setUnseen() 
  {
    imap_clearflag_full($this->stream, $this->uid, self::FLAG_SEEN, ST_UID);
    $this->seen = false;
  }
  
  /**
   * this message is flagged as being a draft 
   *
   * @return bool
   */
  public function getDraft() 
  {
    return $this->draft;
  }
  
//  not ready for production use yet
//    /**
//   * Get special header property. They should start with X-.
//   *
//   * @param string $strName
//   * @return string
//   */
//  public function getProperty($strName)
//  {
//    if (!isset($this->arrHeaders))
//    {
//      $arrHeader = imap_fetchheader($this->stream,
//                                    $this->getUid(),
//                                    FT_UID);
//      
//      // browse array for additional headers
//      if (is_array($arrHeader) && count($arrHeader))
//      {
//        $this->arrHeader = array();
//        foreach($arrHeader as $strLine) 
//        {
//          // is line with additional header?
//          if (eregi("^X-", $strLine)) {
//            // separate name and value
//            eregi("^([^:]*): (.*)", $line, $arg);
//            $this->arrHeaders[$arg[1]] = $arg[2];
//          }
//        }
//      }
//    }
//    
//    $strReturn = false;
//    
//    if (isset($this->arrHeaders[$strName]))
//    {
//      $strReturn = $this->arrHeaders[$strName];
//    }
//    
//    return $strReturn; 
//  }
  
}
?>