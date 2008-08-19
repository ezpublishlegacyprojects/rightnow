<?php
/**
 * File containing the RightNowRequest class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */


include_once("HTTP/Request.php");

//include_once( eZExtension::baseDirectory() . '/' . nameFromPath(__FILE__) . '/classes/rightnowparameter.php' );
#include_once('extension/rightnow/classes/rightnowparameter.php' );

#include_once( 'lib/ezxml/classes/ezxml.php' );

define( 'RIGHTNOW_RESPONSE_DOM_ROOT_NAME', 'connector_ret' );

class RightNowRequest 
{
	const RESPONSE_DOM_ROOT_NAME = "connector_ret";
    var $parameters = array();
    var $function = null;
    var $id = null;
	function RightNowRequest( $functionname, $id = null )
	{
		$this->function = $functionname;
		$this->id = $id;
	}
	function addParameter( $name, $type, $value )
	{
	    $this->parameters[] = new RightNowParameter( $name, $type, $value );
	}

	function call()
	{
	    if ( !$this->function )
	       return false;
	    
	    $doc = new eZDOMDocument();
        $doc->setName( "connector" );
        $root = $doc->createElementNode( "connector" );
        $doc->setRoot( $root );
        
        $function = $doc->createElementNode( "function" );
        $function->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $this->function ) );
        
        if ( $this->id !== null )
            $function->appendAttribute( eZDOMDocument::createAttributeNode( 'id', $this->id ) );
        
        $root->appendChild( $function );
  
	    $rightnowini = eZINI::instance( 'rightnow.ini' );
	    foreach ( $this->parameters as $parameter )
	    {
            $function->appendChild( $parameter->domNode() );
	    }
	    $req = new HTTP_Request( $rightnowini->variable( 'RightNowSettings', 'APIInterface' ) );
        $req->setMethod( HTTP_REQUEST_METHOD_POST );
        $xmlstr = $doc->toString(); 
        
        eZDebug::writeDebug( $xmlstr, 'RightNow::call() - Request to ' . $rightnowini->variable( 'RightNowSettings', 'APIInterface' ) );
        
        $req->addPostData( "xml_doc", $xmlstr );
        $req->addPostData( "sec_string", $rightnowini->variable( 'RightNowSettings', 'SecretString' ) );
        eZDebug::accumulatorStart( 'rightnow_request', 'rightnow_total', 'Request time' );
        if ( !isset( $GLOBALS['eZRightNowTime'] ) )
        	$GLOBALS['eZRightNowTime'] = 0;

        $start = microtime_float( true );
        if ( !PEAR::isError( $req->sendRequest() ) )
        {
            $response = $req->getResponseBody();
        }
        else
        {
        	$end = microtime_float( true );
        	$GLOBALS['eZRightNowTime'] += $end - $start;
        	eZDebug::accumulatorStop( 'rightnow_request' );
            return false;
        }
        $end = microtime_float( true );
        $GLOBALS['eZRightNowTime'] += (float)$end - (float)$start;
        eZDebug::accumulatorStop( 'rightnow_request' );
        /**
         * SAMPLE return from a request

           <connector_ret>
                <function name="acct_create">
                    <ret_val name="rv" type="integer">89</ret_val>
                </function>
           </connector_ret>
         */
        eZDebug::writeDebug( $response, 'RightNow::call() - Response');
        $xml = new eZXML();
        $dom = $xml->domTree( $response );
        if ( !is_object( $dom ) )
        {
            eZDebug::writeError( 'Parser error', 'RightNowRequest::call()' );
            return false;
        }
        $root =& $dom->get_root();
        if ( $root->name() != RightNowRequest::RESPONSE_DOM_ROOT_NAME )
        {
            eZDebug::writeError( 'Wrong doctype', 'RightNowRequest::call()' );
            return false;
        }

        
        # function part
        $function = $root->firstChild();
        if ( $function->getAttribute( "name" ) == 'search' )
        {
            $return = $this->parseSearch( $function );
            eZDebug::writeDebug( $return, 'RightNow::call() - Parsed response');
            return $return;
        }
        #ret_val part
        $ret_val = $function->firstChild();

        if ( $ret_val->getAttribute( "type" ) == RightNow::DATATYPE_PAIR )
        {
            $return = $this->parsePair( $ret_val );
            eZDebug::writeDebug( $return, 'RightNow::call() - Parsed response');
            return $return;
        }
        else
        {
            $ret_val = $ret_val->firstChild();
            eZDebug::writeDebug( $ret_val->Content, 'RightNow::call() - Parsed response');
            return $ret_val->Content;
        }
	}
	function parsePair( &$domnode )
	{
	    $return = array();
		foreach ( $domnode->children() as $child )
		{
		    if ( $child->getAttribute( "type" ) == RightNow::DATATYPE_PAIR )
		      $return[$child->getAttribute( "name" )] = RightNowRequest::parsePair( $child );
		    else
		    {
		        $ret_val = $child->firstChild();
		        $return[$child->getAttribute( "name" )] = $ret_val->Content;
		    }
		}
		return $return;
	}
	function parseSearch( &$domnode )
	{
	    $return = array();
		foreach ( $domnode->children() as $child )
		{
		    if ( $child->Name == RightNow::DATATYPE_ROW )
		      $return[$child->getAttribute( "id" )] = RightNowRequest::parseSearch( $child );
		    else if ( $child->Name == RIGHTNOW_DATATYPE_COL )
		    {
		        $ret_val = $child->firstChild();
		        $return[$child->getAttribute( "id" )] = $ret_val->Content;
		    }
		}
		return $return;
	}
}
function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
?>