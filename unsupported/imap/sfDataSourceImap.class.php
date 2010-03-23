<?php

/*
 * This file is part of the symfony package.
 * (c) Leon van der Ree <Leon@fun4me.demon.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class implements the interface sfDataSourceInterface for accessing
 * messages stored on an imap-server.
 *
 * 
 * <code>
 * $username = 'user';
 * $password = 'pass';
 * $host = '127.0.0.1';
 * $port = 143;
 * $mailboxName = 'Inbox;
 * $options = array('notls');
 * $imapMessages = new sfDataSourceImap($username, $password, $host, $port, $mailboxName, $options);
 * 
 * foreach ($imapMessages as $message)
 * {
 *   echo $message->getSubject();
 * }
 * </code>
 *
 *
 * @package    symfony
 * @subpackage grid
 * @author     Leon van der Ree <Leon@fun4me.demon.nl>
 * @version    SVN: $Id$
 */
class sfDataSourceImap extends sfDataSource
{
  /**
   * an array that holds (a page of) the messages retreived 
   * from the imap connection
   *
   * @var array[sfDSImapMessage]
   */
  protected
    $messages;

  /**
   * The sort properties
   *
   * @var string
   */
  protected 
    $sortColumn = null,
    $sortOrder = null;
  
  /**
   * flag that defines if to retreive removed messages as well
   *
   * @var bool
   */
  protected $showDeleted;
  
  /**
   * An array with filter properties
   *
   * @see sfDataSourceInterface::setFilter()
   * 
   * @var array
   */
  protected $filters;
  
  /**
   * The stream to the imap-server
   *
   * @var resource
   */
  protected $stream;
  
  /**
   * Connection properties
   *
   * @var string/int
   */
  protected
    $username,
    $password,
    $host,
    $port,
    $mailboxName,
    $options;

  /**
   * hydrates an sfDSImapMessage
   *
   * @param object $header
   * @param resource $stream
   * @return sfDSImapMessage
   */
  public static function hydrate($header, $stream)
  {
    $message = new sfDSImapMessage(
      $stream,
      
      isset($header->subject) ? self::mime_header_to_text($header->subject) : null,
      self::mime_header_to_text($header->from),
      isset($header->to) ? self::mime_header_to_text($header->to) : null, 
      new DateTime($header->date),
      self::mime_header_to_text($header->message_id),
      isset($header->references) ? self::mime_header_to_text($header->references) : null,
      isset($header->in_reply_to) ? self::mime_header_to_text($header->in_reply_to) : null,
      $header->size,
      $header->uid,
      $header->msgno,
      ($header->recent == 0) ? false : true,
      ($header->flagged == 0) ? false : true,
      ($header->answered == 0) ? false : true,
      ($header->deleted == 0) ? false : true,
      ($header->seen == 0) ? false : true,
      ($header->draft == 0) ? false : true
    );
    
    return $message;
  }
  
  /**
   * Decodes MIME message header extensions that are non ASCII text (see » RFC2047). 
   * 
   * @param string $text
   * @return string
   */
  protected static function mime_header_to_text($text)
  {
    $elements = imap_mime_header_decode($text);
    
    $returnText = "";
    for($i = 0; $i < count($elements); $i++)
    {
      $returnText .= $elements[$i]->text . " ";
    }
    
    return trim($returnText);
  }  
    
  /**
   * Constructor.
   *
   * @param string  $username    the username
   * @param string  $password    the password
   * @param string  $host        the hostname
   * @param integer $port        the port
   * @param string  $mailboxName the mailbox-name 
   * @param array   $options     an array with options
   * @param bool    $showDeleted a flag that defines if you want to retreive delete messages as well
   *   
   * @throws InvalidArgumentException  Throws an exception if the given array
   *                                   is not formatted correctly
   */
  public function __construct($username, $password, $host, $port, $mailboxName, array $options, $showDeleted = false)
  {
    if(!extension_loaded('imap')) throw new Exception('IMAP extension needed for this class');
    
    $this->username    = $username;
    $this->password    = $password;
    $this->host        = $host;
    $this->port        = $port;
    $this->mailboxName = $mailboxName;    
    $this->options     = $options;
    $this->showDeleted = $showDeleted;
  }

  /**
   * Makes the connection to the imap-server for the specified user
   *
   * @throws Exception  Throws an exception when the connection was not made succesfully
   */
  protected function connect()
  {
    $options = "";
    if(!empty($this->options) && is_array($this->options))
    {
      foreach ($this->options as $option)
      {
        $options .= "/" . $option;
      }
    }

    $adress = '{'.$this->host.':'.$this->port.$options.'}'.$this->mailboxName;
    
    $time = time();
    // IF THIS IS SLOW, PLEASE MAKE SURE rDNS IS ENALBED ON YOUR SYSTEM 
    // (one solution is to place the ip of your mail-server in your /etc/hosts file) 
    $this->stream = @imap_open($adress, $this->username, $this->password);
    $time = (time()-$time);
    if ($time >= 4)
    {
      sfContext::getInstance()->getLogger()->notice(
                    'Imap-login is slow! 
                     Please make user rDNS is enabled on your system. 
                     Tip you can add your mail-server ip to your hosts-file.');
    }
        
    if(!$this->stream)
    {
      throw new sfDataSourceImapConnectionException('unable to connect user "'.$this->username.'" to imap server: '.$adress);
    }
  }
  
  /**
   * returns the connection, makes a connection if not already done
   *
   * @return resource
   */
  public function getConnection()
  {
    if(!$this->stream)
    {
      $this->connect();
    }
    
    return $this->stream;
  }  
    
  /**
   * Closes the imap Connection
   *
   */
  protected function closeConnection()
  {
    imap_close($this->stream);
  }
  
  /**
   * changes the mailbox
   *
   * @param string $mailBoxName
   */
  public function changeMailbox($mailBoxName)
  {
    $this->mailboxName = $mailBoxName;

    // change to this mailbox immdediately, if there already was a connection
    if($this->stream)
    {
      $success = imap_reopen($this->stream, $this->mailboxName);
      
      if (!$success)
      {
        throw new Exception('Could not change to mailbox: '.$this->mailboxName);
      }
    }
  }
  
  /**
   * loads an array of hydrated (sfDSImapMessage) messages
   * 
   * there now is limited support for filtering with ORs 
   *
   */
  protected function loadMessages()
  {
    // if not yet loaded, get messages
    if (!isset($this->messages))
    {
      $this->getConnection();
      
      // test if sorting asc or descending
      $reverse = ($this->sortOrder == self::DESC) ? 1 : 0;

      $filterCriteria = $this->getFilterCriteria();
      $sortColumn = strtolower($this->sortColumn);
      
      // lookup table for imap-header-property-names to imap-sort-constant 
      $sortMapping = array(
        'date'    => SORTARRIVAL,
        'from'    => SORTFROM,
        'to'      => SORTTO,
        'cc'      => SORTCC,
        'subject' => SORTSUBJECT,
        'size'    => SORTSIZE,
      );
      
      // get translation for sorting
      if (isset($sortMapping[$sortColumn]))
      {
        // regular search
        if (!is_array($filterCriteria))
        {
          $msgNrs = imap_sort($this->stream, $sortMapping[$sortColumn], $reverse, SE_UID, $filterCriteria);
        }
        // support for OR in search
        else
        {
          // get all messages
          $allMsgNrs = imap_sort($this->stream, $sortMapping[$sortColumn], $reverse, SE_UID);
          $results = array();
          
          // request results for every or
          foreach ($filterCriteria as $filterCriterion)
          {
            $newResults = imap_sort($this->stream, $sortMapping[$sortColumn], $reverse, SE_UID, $filterCriterion);
            // do a union with previous results
            if ($result != false)
            {
              $results = array_merge($results, $newResults);
            }
          }
          
          // return the intersection
          $msgNrs = array_intersect($allMsgNrs, $results); 
        }
      }
      else
      {
        $filterCriteria = ($filterCriteria != null) ? $filterCriteria : 'ALL';
        
        // regular search
        if (!is_array($filterCriteria))
        {
          $msgNrs = imap_search($this->stream, $filterCriteria, SE_UID);          
        }
        // support for OR in search
        else
        {
          // get all messages
          $allMsgNrs = imap_search($this->stream, 'ALL', SE_UID);
          $results = array();
          
          // request results for every or
          foreach ($filterCriteria as $filterCriterion)
          {
            $newResults = imap_search($this->stream, $filterCriterion, SE_UID);
            // do a union with previous results
            if ($newResults != false)
            {
              $results = array_merge($results, $newResults);
            }
          }
          
          // return the intersection
          $msgNrs = array_intersect($allMsgNrs, $results); 
        }        
        
        if ($this->sortOrder == self::DESC)
        {
          $msgNrs = array_reverse($msgNrs);
        }
      }
      
      // if no results found, set to empty array
      if ($msgNrs == false)
      {
        $msgNrs = array();
      }
      
      // use the offset and limit
      if ($this->getLimit())
      {
        $msgNrs = array_slice($msgNrs, $this->getOffset(), $this->getLimit());
      }
      
      // used for the iterator
      $this->messages = $this->getByIds($msgNrs, $this->showDeleted);
    }
  }

  /**
   * gets an message from the mailbox
   *
   * @param int $uid
   * @return sfDSImapMessage or null if no results
   */
  public function getById($uid)
  {
    $imapBerichten = $this->getByIds(array($uid));
    
    if (count($imapBerichten) == 0)
    {
      $imapBericht = null;
    }
    else
    {
      $imapBericht = $imapBerichten[0];
    }
    
    return $imapBericht;    
  }
  
  /**
   * returns an array of messages by UID
   * 
   * this method doesn't influence the iterator, 
   * nor does it care about the sorting, filtering, offset and limit of this datasource
   *
   * @param array $uids
   * @return array[sfDSImapMessage]
   */
  public function getByIds($uids)
  {
// the alternatives:
//      $header = imap_headerinfo($this->stream, $offset);
//      return retrieve_message($this->stream, $offset))
    $this->getConnection();
    $messages = array();
    
    $sequence = implode(',', $uids);
    $headers = imap_fetch_overview($this->stream, $sequence, FT_UID);
    
    // place in new array to reorden
    foreach ($headers as $header)
    {
      $location = array_search($header->uid, $uids); 
      $messages[$location] = self::hydrate($header, $this->stream);
    }

    // sort according to specified indexes.
    ksort($messages);
    
    return $messages;
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
    // load mails
    $this->loadMessages();
    
    if (!$this->valid())
    {
      throw new OutOfBoundsException(sprintf('The result with index %s does not exist', $this->key()));
    }

    return $this->messages[$this->key()];
  }

  /**
   * Returns the value of the given column in the current row returned by current()
   *
   * @param  string $column The name of the column
   * @return mixed          The value in the given column of the current row
   */
  public function offsetGet($column)
  {
    $current = $this->current();
    
    return call_user_func(array($current, 'get'.$column));
  }

  /**
   * Returns the number of records in the data source. If a limit is set with
   * setLimit(), the maximum return value is that limit. You can use the method
   * countAll() to count the total number of rows regardless of the limit.
   *
   * <code>
   * $source = new sfDataSourceImap(...TODO: );
   * echo $source->count();    // returns "100"
   * $source->setLimit(20);
   * echo $source->count();    // returns "20"
   * </code>
   *
   * @return integer The number of messages for this connection
   */
  public function count()
  {
    $all   = $this->countAll();
    $count = $all - $this->getOffset();

    return $this->getLimit()==0 ? $count : min($this->getLimit(), $count);
  }
  
  /**
   * @see sfDataSourceInterface::countAll()
   */
  public function countAll()
  {
    $this->getConnection();
    
    $filterCriteria = $this->getFilterCriteria();
    // if a filter has been set, count filtered
    if ($filterCriteria != null)
    {
      // regular search
      if (!is_array($filterCriteria))
      {
        $msgNrs = imap_search($this->stream, $filterCriteria, SE_UID);          
      }
      // support for OR in search
      else
      {
        // get all messages
        $allMsgNrs = imap_search($this->stream, 'ALL', SE_UID);
        $results = array();
        
        // request results for every or
        foreach ($filterCriteria as $filterCriterion)
        {
          $newResults = imap_search($this->stream, $filterCriterion, SE_UID);
          // do a union with previous results
          if ($newResults != false)
          {
            $results = array_merge($results, $newResults);
          }
        }
        
        // return the intersection
        $msgNrs = array_intersect($allMsgNrs, $results); 
      }      
      
      if ($msgNrs == false)
      {
        $nrMessages = 0;
      } 
      else
      {
        $nrMessages = count($msgNrs);
      }
    }
    // else count all
    else
    {
      $nrMessages = imap_num_msg($this->stream);  
    }
    
    return $nrMessages;
  }
  
  /**
   * Translates the array of filter properties to a imap-criteria
   *
   * you can extend this class and make this function return an array to add
   * (limited) support for OR operations, by returning an array, instead of a string
   * see also loadMessages and countAll
   * 
   * by default we return a string, which contains only AND operations
   * 
   * @return string
   */
  protected function getFilterCriteria()
  {
    if (!$this->showDeleted)
    {
      $this->filters['UNDELETED'] = array('value' => '');
    }
          
    if (count($this->filters) == 0)
    {
      return null;
    }
    
    $criteria = array();
    foreach ($this->filters as $filter => $options)
    {
      $criterium = strtoupper($filter);
      if ($options['value'] != '')
      {
        $criterium .= ' "'.$options['value'].'"';
      }
      
      $criteria[] = $criterium;
    }
    
    return implode(' ', $criteria);
  }
  
  /**
   * @see sfDataSourceInterface::requireColumn()
   */
  public function requireColumn($column)
  {
    // TODO: allow getting complete message, including body and attachements,
    // currently only header is retreived
  }

  /**
   * @see sfDataSource::doSort()
   */
  protected function doSort($column, $order)
  {
    $this->sortColumn = $column;
    $this->sortOrder = $order;
  }
  
  /**
   * @see sfDataSourceInterface
   */
  public function setFilter($fields)
  {
    $this->filters = $fields;
  }
    
}