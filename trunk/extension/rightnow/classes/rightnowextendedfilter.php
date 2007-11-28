<?php
/**
 * File containing the RightNowExtendedFilter class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
/*
 * a Filter to filter objectrelationslist by object_id
 * and search by text
 fetch('content','list',hash('parent_node_id', 2,
													
			'extended_attribute_filter',  
			hash( 'id', 'RelationFilter',
                  'params', hash( 'relations', array(234, 233),
                  				  'search_text', 'Text to search in data_text of ezcontentobject_attribute' ) )
	                                                
	  ) )
 * 
 */
class RightNowExtendedFilter
{
    /*!
     Constructor
    */
    function RightNowExtendedFilter()
    {
        // Empty...
    }

    function createSqlParts( $params )
    {
        $db = eZDB::instance();    
        
        
        $sqlCondArray = array();
        if ( isset( $params['relations'] ) and $params['relations'] )
        {
             $relations =  $params['relations'];
        }
        else
        {
             $relations = array();
        }
        
    	if ( isset( $params['search_text'] ) and $params['search_text'] )
        {
             $searchtext = $params['search_text'];
        }
        else
        {
             $searchtext = '';
        }
        
        if ( !isset( $params['condition'] ) and in_array( $params['condition'], array( 'and', 'or' ) ) )
        {
             $condition =  $params['condition'];
        }
        else
        {
             $condition = 'and';
        }
        $i=0;
        foreach ( $relations as $relation )
        {
            if ( is_numeric( $relation ) and $relation > 0 )
            {
                $sqlCondArray[] = ' e.to_contentobject_id = '. $db->escapeString( $relation );
                $i++;
            }
        }
        
        
        
        if ( count( $sqlCondArray ) > 0 || $searchtext != '') 
        {
			$sqlCond = "";
        	
			// query for objectrelationlist
        	if ( $searchtext != '' ) 
        	{
	        	// query for textsearch
				//SELECT e.id as object_id, e.current_version, e.contentclass_id, ea.id, ea.data_text
	        	$sqlCond .= ' ezcontentobject_tree.contentobject_id in (
	                    SELECT e.id
						FROM ezcontentobject e, ezcontentobject_attribute ea, ezcontentobject_version ev
						WHERE e.id = ev.contentobject_id
						AND e.current_version = ev.version
						AND e.id = ea.contentobject_id
						AND e.current_version = ea.version
						AND ea.data_text like \'%'.$searchtext.'%\'
						GROUP BY e.id ) AND ';
        	}
        	// query for objectrelationlist
        	if ( count( $sqlCondArray ) > 0 ) 
        	{
        		$sqlCond .= ' ezcontentobject_tree.contentobject_id in (
                    SELECT e.from_contentobject_id 
					FROM ezcontentobject_link e, ezcontentobject e1
                    WHERE ( ' . implode( ' or ', $sqlCondArray ) . ' ) AND e1.current_version = e.from_contentobject_version 
					AND e1.id =e.from_contentobject_id					
                    GROUP BY from_contentobject_id					
                    HAVING count(e.to_contentobject_id) = ' . $i . ' ) AND ';
        	}      

            return array( 'tables' => $sqlTables, 'joins'  => $sqlCond );
        }
        else
        {
            return array( 'tables' => false, 'joins'  => false );
        }
    }
}
?>