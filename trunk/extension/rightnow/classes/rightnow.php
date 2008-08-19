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

#include_once('extension/rightnow/classes/rightnowrequest.php');
#include_once('extension/rightnow/classes/rightnowcustomization.php');


/*
RightNow class with static function API calls
*/
class RightNow
{
	const DATATYPE_PAIR    = "pair";
	const DATATYPE_INTEGER = "integer";
	const DATATYPE_STRING  = "string";
	const DATATYPE_ROW     = "row";
	const DATATYPE_COL     = "col";
	const DATATYPE_TIME    = "time";

	const CUSTOMFIELD_DATATYPE_MENU      = "1";
	const CUSTOMFIELD_DATATYPE_RADIO     = "2";
	const CUSTOMFIELD_DATATYPE_INTEGER   = "3";
	const CUSTOMFIELD_DATATYPE_DATETIME  = "4";
	const CUSTOMFIELD_DATATYPE_TEXTFIELD = "5";
	const CUSTOMFIELD_DATATYPE_TEXTAREA  = "6";
	const CUSTOMFIELD_DATATYPE_DATE      = "7";
	const CUSTOMFIELD_DATATYPE_OPTIN     = "8";

	function RightNow()
	{
		
	}
	
	function getCustomization()
	{
	    $ini = eZINI::instance( 'rightnow.ini' );
		include_once( $ini->variable( 'RightNowSettings', 'CustomizationClassPath' ) );
		$classname = $ini->variable( 'RightNowSettings', 'CustomizationClass' );
		return new $classname();
	}
	function createCustomer( $contact )
	{
		$req = new RightNowRequest( 'contact_create' );
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $contact );
		$req->addParameter( "flags", RightNow::DATATYPE_INTEGER, '0x00002' );
	    return $req->call();
	}
	
	
	function updateCustomer( $contact, $c_id )
	{
		$req = new RightNowRequest( 'contact_update' );
		$contact = array_merge( $contact, array( "c_id" => (int)$c_id ) );
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $contact );
		$req->addParameter( "flags", RightNow::DATATYPE_INTEGER, '0x00002' );
	    return $req->call();
	}
	
	function getCustomer( $c_id )
	{
		$req = new RightNowRequest( 'contact_get' );
		$contact = array( "id" => (int)$c_id );
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $contact );
	    return $req->call();
	}
	function getUniqueCustomer( $contact )
	{
	 
	    $db = eZDB::instance();
	    $sql = "SELECT * FROM contacts WHERE email = '" . $db->escapeString( $contact["email"] ) . "'";
		return RightNow::sql( $sql );
	}
	
	function getOrganisationByCustomer( $c_id )
	{
	 
	    $db = eZDB::instance();
	    $sql = "SELECT org_id FROM contacts WHERE c_id = '" . $db->escapeString( $c_id ) . "'";
		return RightNow::sql( $sql );
	}
	
	function updateOrganisation( $organisation, $org_id )
	{
		$req = new RightNowRequest( 'org_update' );
		$contact = array_merge( $organisation, array( "org_id" => (int)$org_id ) );
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $contact );
		$req->addParameter( "flags", RightNow::DATATYPE_INTEGER, '0x00002' );
	    return $req->call();
	}
	
	function createOrganisation( $organization )
	{
		$req = new RightNowRequest( 'org_create' );
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $organization );
		$req->addParameter( "flags", RightNow::DATATYPE_INTEGER, '0x00002' );
	    return $req->call();
	}
	
	function getCustomFieldValue( $customer, $customfield_id )
	{
	    if ( array_key_exists( 'custom_field', $customer ) )
	    {
	        foreach ( $customer['custom_field'] as $field )
	        {
	            if ( $field['cf_id'] == $customfield_id )
	            {
	                
	                switch ( (int)$field['data_type'] )
	                {
	                    
	                    case RightNow::CUSTOMFIELD_DATATYPE_MENU:
	                        {
	                            return $field['val_int'];
	                        }break;
	                    case RightNow::CUSTOMFIELD_DATATYPE_RADIO:
	                    case RightNow::CUSTOMFIELD_DATATYPE_OPTIN:
	                    case RightNow::CUSTOMFIELD_DATATYPE_INTEGER:
	                        {
	                            return $field['val_int'];
	                        }break;
	                    case RightNow::CUSTOMFIELD_DATATYPE_DATE:
	                    case RightNow::CUSTOMFIELD_DATATYPE_DATETIME:
	                        {
	                            return $field['val_time'];
	                        }break;
	                    case RightNow::CUSTOMFIELD_DATATYPE_TEXTAREA:
	                    case RightNow::CUSTOMFIELD_DATATYPE_TEXTFIELD:
	                        {

	                            return $field['val_str'];
	                        }break;
	                    default:
	                        {
	                            // Do nothing for now
	                        }break;
	                }
	            }
	        }
	    }
	}
	function addCustomField( &$stack, $id, $value , $datatype = RightNow::CUSTOMFIELD_DATATYPE_INTEGER )
	{
	    //@TODO  ncie to have if $datatype = false we autodetermine the datatype
		$key = 'cf_item' . ( count($stack) + 1 ) ;

		switch ($datatype)
		{
		    case RightNow::CUSTOMFIELD_DATATYPE_MENU:
		    {
		        $array = array();
		        $array[] = new RightNowParameter( 'cf_id', RightNow::DATATYPE_INTEGER, $id );
		        $array[] = new RightNowParameter( 'data_type', RightNow::DATATYPE_INTEGER, $datatype );
		        $array[] = new RightNowParameter( 'val_int', RightNow::DATATYPE_INTEGER, $value);
		        $stack[$key] = $array;
		    }break;
		    case RightNow::CUSTOMFIELD_DATATYPE_RADIO:
		    case RightNow::CUSTOMFIELD_DATATYPE_OPTIN:
		    case RightNow::CUSTOMFIELD_DATATYPE_INTEGER:
		    {
		        $array = array();
		        $array[] = new RightNowParameter( 'cf_id', RightNow::DATATYPE_INTEGER, $id );
		        $array[] = new RightNowParameter( 'data_type', RightNow::DATATYPE_INTEGER, $datatype );
		        $array[] = new RightNowParameter( 'val_int', RightNow::DATATYPE_INTEGER, $value);
		        $stack[$key] = $array;
		    }break;
		    case RightNow::CUSTOMFIELD_DATATYPE_DATE:
		    case RightNow::CUSTOMFIELD_DATATYPE_DATETIME:
		    {
		        $array = array();
		        $array[] = new RightNowParameter( 'cf_id', RightNow::DATATYPE_INTEGER, $id );
		        $array[] = new RightNowParameter( 'data_type', RightNow::DATATYPE_INTEGER, $datatype );
		        $array[] = new RightNowParameter( 'val_time', RightNow::DATATYPE_TIME, $value);
		        $stack[$key] = $array;
		    }break;
		    case RightNow::CUSTOMFIELD_DATATYPE_TEXTAREA:
		    case RightNow::CUSTOMFIELD_DATATYPE_TEXTFIELD:
		    {
		        $array = array();
		        $array[] = new RightNowParameter( 'cf_id', RightNow::DATATYPE_INTEGER, $id );
		        $array[] = new RightNowParameter( 'data_type', RightNow::DATATYPE_INTEGER, $datatype );
		        $array[] = new RightNowParameter( 'val_str', RightNow::DATATYPE_STRING, $value);
		        $stack[$key] = $array;
		    }break;
		    default:
		    {
		        // Do nothing for now
		    }break;
		}
		
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

        if ( $rn_cust_ID = RightNow::getCustomerByLogin($login) )
        {
            $rn_cust = RightNow::getCustomer($rn_cust_ID);
            $rightnowcust=true;
        }
        else 
        {
            $rightnowcust=false;
        }
        if( $rightnowcust )
            $org_id  = RightNow::getOrganisationByCustomer( $rn_cust_ID );
        $remoteexp=explode(":", $remoteID);
        
        if ( $remoteexp[0]=="RightNow" AND $remoteexp[1]=="customers" AND $remoteexp[2]==$rn_cust_ID )
            $remoteidcheck=true;
        else 
            $remoteidcheck=false;
        $contact = array();
        $organisation = array();
        $custom = RightNow::getCustomization();
        $custom->fillContact( $contact, $contentObjectID, $organisation );
        if( is_numeric( $org_id ) )
        {
            $orga_id = $org_id;
            RightNow::updateOrganisation( $organisation, $org_id );
        }
        else
            $orga_id = RightNow::createOrganisation( $organisation );
        $contact["org_id"] = (int)$orga_id;
        
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
            
            
            #include_once("kernel/classes/ezcontentobject.php");
            $contentObject = eZContentObject::fetch( $contentObjectID );
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
            #include_once("kernel/classes/ezcontentcachemanager.php");
            eZContentCacheManager::clearContentCache($contentObjectID);
        }
	}
	
	function getCustomerByLogin( $loginname )
	{
	    $db = eZDB::instance();
	    $sql = "SELECT c_id FROM contacts WHERE login = '" . $db->escapeString( $loginname ) . "'";
		return RightNow::sql( $sql );
	}
	
	function getCustomerByEmail( $email )
	{
	    $db = eZDB::instance();
	    $sql = "SELECT c_id FROM contacts WHERE email = '" . $db->escapeString( $email ) . "'";
		return RightNow::sql( $sql );
	}

	function sql( $sql, $returntype = RightNow::DATATYPE_INTEGER )
	{
	    if ( $returntype = RightNow::DATATYPE_INTEGER )
		  $req = new RightNowRequest( 'sql_get_int', 'sql_str' );
		else
		  $req = new RightNowRequest( 'sql_get_str', 'sql_str' );
		$req->addParameter( "sql", RightNow::DATATYPE_STRING, $sql );
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
		#$db = eZDB::instance();
	    $sql = "SELECT max( a_id ) FROM answers";
		return RightNow::sql( $sql, RightNow::DATATYPE_INTEGER );		
	}
	
	
	
	function getFAQById( $a_id )
	{
	    $req = new RightNowRequest( 'ans_get' );
		$req->addParameter( "a_id", RightNow::DATATYPE_INTEGER, $a_id );
	    return $req->call();
	}
	
	
	
	function getSearch( $view_id, $max_rows = false )
	{
		if( $max_rows === false )
			$max_rows =1000;
		
		$req = new RightNowRequest( 'search' );
		$req->addParameter( "view_id", RightNow::DATATYPE_INTEGER, $view_id );
		
		$req->addParameter( "max_rows", RightNow::DATATYPE_INTEGER, $max_rows );		
		
		
	    return $req->call();
		
		
	}
	

	function getMenuItemById( $hier_menu_id )
	{
		$req = new RightNowRequest( 'hiermenu_get' );
		$req->addParameter( "id", RightNow::DATATYPE_INTEGER, $hier_menu_id );
		$req->addParameter( "tpl", RightNow::DATATYPE_INTEGER, 14 );
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
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $answerArr );
	
	    return $req->call();
	}
	
	
		/*
	
		$metaAnswerArr['summary']=(string)'a summary';
		
		
		@return m_id if created
	*/	
	function createMetaAnswer( $metaAnswerArr )
	{
		$req = new RightNowRequest( 'meta_ans_create' );
		$req->addParameter( "args", RightNow::DATATYPE_PAIR, $metaAnswerArr );
	
	    return $req->call();
	}
	
}
?>