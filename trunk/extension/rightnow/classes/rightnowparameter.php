<?php
/**
 * File containing the RightNowParameter class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
define( "RIGHTNOW_DATATYPE_PAIR", 'pair' );
define( "RIGHTNOW_DATATYPE_INTEGER", 'integer' );
define( "RIGHTNOW_DATATYPE_STRING", 'string' );
define( "RIGHTNOW_DATATYPE_ROW", 'row' );
define( "RIGHTNOW_DATATYPE_COL", 'col' );

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

           if ( !is_array( $content ) )
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
		
        if ( $this->type != RIGHTNOW_DATATYPE_PAIR )
		{
		    $parameter->appendChild( $doc->createTextNode( $this->value ) );
		}
		else
		{
		    foreach ( $this->value as $key => $content )
		    {
                if ( !is_array( $content ) )
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