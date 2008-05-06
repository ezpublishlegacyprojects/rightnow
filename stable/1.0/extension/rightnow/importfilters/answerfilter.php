<?php
/**
 * File containing the answerfilter class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class answerfilter extends eZImportConverter
{
	function answerfilter()
	{
		
	}
	function filter ( &$data )
	{
	    
		$search = array ( '/\<\/as-html\>/Ui',
						  '/\<as-html\>/Ui'
						   );

		$replace = array (  "",
							""
							 );
        
		$data = preg_replace( $search, $replace, $data );
		return $data;
	}
}
?>