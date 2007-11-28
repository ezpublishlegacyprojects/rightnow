<?php
/**
 * File containing the RightNow class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
//include_once( eZExtension::baseDirectory() . '/' . nameFromPath(__FILE__) . '/classes/rightnowrequest.php' );

include_once('extension/rightnow/classes/rightnowrequest.php');


/*
RightNow class with static function API calls
*/
class RightNow
{

	function RightNow()
	{
		
	}
	/*
	
	    $contact['sa_state']=(int)0;
		$contact['ma_state']=(int)0;
		$contact['css_state']=(int)1;
		$contact['login']=(string)'xrow';
		$contact['first_name']=(string)'Björn';
		$contact['last_name']=(string)'Dieding';
	
	*/
	function createCustomer( $contact )
	{
		$req = new RightNowRequest( 'contact_create' );
		$req->addParameter( "args", RIGHTNOW_DATATYPE_PAIR, $contact );
		$req->addParameter( "flags", RIGHTNOW_DATATYPE_INTEGER, '0x00002' );
	    return $req->call();
	}
	
	function updateCustomer( $contact, $c_id )
	{
		$req = new RightNowRequest( 'contact_update' );
		$req->addParameter( "args", RIGHTNOW_DATATYPE_PAIR, $contact );
		$req->addParameter( "flags", RIGHTNOW_DATATYPE_INTEGER, '0x00002' );
		$req->addParameter( "c_id", RIGHTNOW_DATATYPE_INTEGER, $c_id );
	    return $req->call();
	}
	
	function getCustomer( $c_id )
	{
		$req = new RightNowRequest( 'contact_get' );
		$req->addParameter( "c_id", RIGHTNOW_DATATYPE_INTEGER, $c_id );
	    return $req->call();
	}
	function getUniqueCustomer( $contact )
	{
	 
	    $db = eZDB::instance();
	    $sql = "SELECT * FROM contacts WHERE email = '" . $db->escapeString( $contact["email"] ) . "'";
		return RightNow::sql( $sql );
	}	
	
	function storeCustomer( $contentObjectID )
	{
		$c_user = eZUser::fetch( $contentObjectID, true );
        $co = eZContentObject::fetch( $contentObjectID );
        if ( !is_object( $co ) )
        	return false;
        $c_user_dm = $co->dataMap();
        $remoteID=$co->RemoteID;
        $login=&$c_user->attribute("login");
        $email=&$c_user->attribute("email");
        $password=&$c_user->attribute("password_hash");
        
        $firstname=$c_user_dm["first_name"]->DataText;
        $lastname=$c_user_dm["last_name"]->DataText;
        $street=$c_user_dm["street"]->DataText;
        $phone=$c_user_dm["phone"]->DataText;
        $optin=$c_user_dm["optin"]->DataInt;
        $organisation=$c_user_dm["organisation"]->DataText;
        $title=$c_user_dm["title"]->DataText;
        $postal_code=$c_user_dm["postal_code"]->DataText;
        
        if ( $rn_cust_ID = RightNow::getCustomerByLogin($login) )
        {
            $rn_cust = RightNow::getCustomer($rn_cust_ID);
            $rightnowcust=true;
            #var_dump($rn_cust);

        }
        else 
        {
            $rightnowcust=false;
        }
        
        
        $remoteexp=explode(":", $remoteID);
        
        if ( $remoteexp[0]=="RightNow" AND $remoteexp[1]=="customers" AND $remoteexp[2]==$rn_cust_ID )
            $remoteidcheck=true;
        else 
            $remoteidcheck=false;
            
        $contact['sa_state']=(int)0;
        $contact['ma_state']=(int)0;
        $contact['css_state']=(int)1;
        $contact['login']=(string)$login;
        $contact['first_name']=(string)$firstname;
        $contact['last_name']=(string)$lastname;
        $contact['ma_opt_in']=(int)$optin;
        $contact['ph_office']=(string)$phone;
        $contact['ma_org_name']=(string)trim($organisation);
        $contact['street']=(string)$street;
        $contact['email']=(string)$email;
        $contact['password']=(string)trim($password);
        $contact['title']=(string)$title;
        $contact['postal_code']=(string)$postal_code;
        #var_dump($contact);
        
        
        if ( $remoteidcheck and $rightnowcust)
        {
            $returnvalue=RightNow::updateCustomer($contact, (int)$rn_cust_ID);
            if ( $returnvalue=="1" )
                eZDebug::writeDebug( 'RightNow::createCustomer -  USER UPDATED ', 'RightNow' );
            else
                eZDebug::writeDebug( 'RightNow::createCustomer -  USER UPDATE FAILED FOR UNKNOWN REASON'.$returnvalue, 'RightNow' );
        }
        elseif ( !$rightnowcust ) 
        {
            
            $returnvalue=RightNow::createCustomer( $contact );
            if ( $returnvalue >= "1" )
                eZDebug::writeDebug( 'RightNow::createCustomer -  NEW USER AT RIGHTNOW REGISTERED ', 'RightNow' );
            elseif ( $returnvalue >= "-2" )
                eZDebug::writeDebug( 'RightNow::createCustomer -  NEW USER  REGISTRATION AT RIGHTNOW FAILED - E-MAIL ADDRESS ALREADY EXISTS', 'RightNow' );
            else
                eZDebug::writeDebug( 'RightNow::createCustomer -  NEW USER  REGISTRATION AT RIGHTNOW FAILED FOR UNKNOWN REASON '.$returnvalue, 'RightNow' );

           
            /*
                RemoteID updaten
            */
            
            
            include_once("kernel/classes/ezconobject.php");
                            $contentObject =& eZContentObject::fetch( $contentObjectID );
                            $remoteID = "RightNow:customers:" . $returnvalue;
                            $contentObject->setAttribute( 'remote_id', $remoteID );
                            $contentObject->store();
        }
        else 
        {
            eZDebug::writeDebug( 'RightNow::createCustomer -  NEW USER  REGISTRATION AT RIGHTNOW FAILED - Username already exists in Rightnow - eZUser created'.$returnvalue, 'RightNow' );
        }
        
        if ( $contentObjectID >= 2 )
        {
            include_once("kernel/classes/ezcontentcachemanager.php");
            eZContentCacheManager::clearContentCache($contentObjectID);
        }
        
	}
	
	function getCustomerByLogin( $loginname )
	{
	    $db = eZDB::instance();
	    $sql = "SELECT c_id FROM contacts WHERE login = '" . $db->escapeString( $loginname ) . "'";
		return RightNow::sql( $sql );
	}

	function sql( $sql, $returntype = RIGHTNOW_DATATYPE_INTEGER )
	{
	    if ( $returntype = RIGHTNOW_DATATYPE_INTEGER )
		  $req = new RightNowRequest( 'sql_get_int', 'sql_str' );
		else
		  $req = new RightNowRequest( 'sql_get_str', 'sql_str' );
		$req->addParameter( "sql", RIGHTNOW_DATATYPE_STRING, $sql );
	    return $req->call();
	}
	
	
	/* functions for FAQ import */
	
	/**
	*  @return max number of Faq id (a_id)
	*	so we can loop all the data with single
	*	requests
	*/
	function getAllMaxIdFAQ()
	{
		$db = eZDB::instance();
	    $sql = "SELECT max( a_id ) FROM answers";
		return RightNow::sql( $sql, RIGHTNOW_DATATYPE_INTEGER );		
	}
	
	
	
	function getFAQById( $a_id )
	{
	    $req = new RightNowRequest( 'ans_get' );
		$req->addParameter( "a_id", RIGHTNOW_DATATYPE_INTEGER, $a_id );
	    return $req->call();
	}
	
	
	
	function getSearch( $view_id, $max_rows = false )
	{
		if( $max_rows === false )
			$max_rows =1000;
		
		$req = new RightNowRequest( 'search' );
		$req->addParameter( "view_id", RIGHTNOW_DATATYPE_INTEGER, $view_id );
		
		$req->addParameter( "max_rows", RIGHTNOW_DATATYPE_INTEGER, $max_rows );		
		
		
	    return $req->call();
		
		
	}
	

	function getMenuItemById( $hier_menu_id )
	{
		$req = new RightNowRequest( 'hiermenu_get' );
		$req->addParameter( "id", RIGHTNOW_DATATYPE_INTEGER, $hier_menu_id );
		$req->addParameter( "tpl", RIGHTNOW_DATATYPE_INTEGER, 14 );
	    return $req->call();
	}
	

	/*
		$answerArr['access_mask']=(string)1;		
		$answerArr['assgn_acct_id']=(int)59;
		$answerArr['assgn_group_id']=(int)13;
 		$answerArr['description']=(string)'a description';
		$answerArr['summary']=(string)'a summary';
		$answerArr['solution']=(string)'a solution';
		$answerArr['status_id']=(int)7;   //  public(4), private(5), proposed(6), review (7)
		$answerArr['keywords']=(string)'word1,word2';
		$answerArr['lang_id']=(int)5;
		$answerArr['m_id']=(int)1;
		
		@return a_id if created
	*/	
	function createAnswer( $answerArr )
	{
		
		if( !array_key_exists('m_id'))
		{
			$metaAnswerArray['summary']=$answerArr['summary'];
			$m_id = RightNow::createMetaAnswer( $metaAnswerArray );
			
			if( (int)$m_id > 0)
				$answerArr['m_id'] = (int)$m_id;
			else 
				return -1;
		}
		
		$req = new RightNowRequest( 'ans_create' );
		$req->addParameter( "args", RIGHTNOW_DATATYPE_PAIR, $answerArr );
	
	    return $req->call();
	}
	
	
		/*
	
		$metaAnswerArr['summary']=(string)'a summary';
		
		
		@return m_id if created
	*/	
	function createMetaAnswer( $metaAnswerArr )
	{
		$req = new RightNowRequest( 'meta_ans_create' );
		$req->addParameter( "args", RIGHTNOW_DATATYPE_PAIR, $metaAnswerArr );
	
	    return $req->call();
	}
	
}
?>
