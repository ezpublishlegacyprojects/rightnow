<?php
/**
 * File containing the RightNowParameter class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */

class RightNowParameter 
{
    var $type;
    var $name;
    var $value;
	function RightNowParameter( $name, $type, $value )
	{
	   $this->name = $name;
	   $this->type = $type;
	   $this->value = $value;	
	}
	function addNodeTree( $key, $array )
	{
	    $doc = new eZDOMDocument();
	    $parameter = $doc->createElementNode( "pair" );
        $parameter->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $key ) );
        $parameter->appendAttribute( eZDOMDocument::createAttributeNode( 'type', 'pair' ) );
		
		foreach ( $array as $key => $content )
		{
		   if ( is_object( $content ) )
		   {
                $pair = $doc->createElementNode( "pair" );
                $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $content->name ) );
                $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'type', $content->type ) );
                $pair->appendChild( $doc->createTextNode( $content->value ) );
                $parameter->appendChild( $pair );
                unset( $pair );   
		   }
           elseif ( !is_array( $content ) )
           {
                $pair = $doc->createElementNode( "pair" );
                $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $key ) );
                $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'type', gettype( $content ) ) );
                $pair->appendChild( $doc->createTextNode( $content ) );
                $parameter->appendChild( $pair );
                unset( $pair );
           }
           else
           {
                $parameter->appendChild( $this->addNodeTree( $key, $content ) );
           }
		}
		return $parameter;
	}
	function domNode()
	{
		$doc = new eZDOMDocument();
		$parameter = $doc->createElementNode( "parameter" );
        $parameter->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $this->name ) );
        $parameter->appendAttribute( eZDOMDocument::createAttributeNode( 'type', $this->type ) );
		
        if ( $this->type != RightNow::DATATYPE_PAIR )
		{
		    $parameter->appendChild( $doc->createTextNode( $this->value ) );
		}
		else
		{
		    foreach ( $this->value as $key => $content )
		    {
		        if ( is_array( $content ) and count( $content ) > 0  )
		        {
                    $keys = array_keys( $content );
		        }
		        if ( is_array( $content ) and count( $content ) > 0 and is_object( $content[$keys[0]] ) )
		        {
		            foreach( $content as $paramkey => $rightnowparam )
		            {
                        $pair = $doc->createElementNode( "pair" );
                        $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $paramkey ) );
                        $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'type', $rightnowparam->type ) );
                        $pair->appendChild( $doc->createTextNode( $rightnowparam->value ) );
                        $parameter->appendChild( $pair );
                        unset( $pair );
		            }
		        }
                elseif ( !is_array( $content ) )
                {
                    $pair = $doc->createElementNode( "pair" );
                    $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'name', $key ) );
                    $pair->appendAttribute( eZDOMDocument::createAttributeNode( 'type', gettype( $content ) ) );
                    $pair->appendChild( $doc->createTextNode( $content ) );
                    $parameter->appendChild( $pair );
                    unset( $pair );
                }
                else
                {
                    $parameter->appendChild( $this->addNodeTree( $key, $content ) );
                }
		    }

		}
		return $parameter;
	}
}
?>