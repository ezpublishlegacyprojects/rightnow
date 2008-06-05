<?php
/**
 * File containing the RightNowUserType class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
define( "EZ_WORKFLOW_TYPE_RIGHTNOW_USER_ID", "rightnowuser" );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath(__FILE__) . '/classes/rightnow.php' );

class RightNowUserType extends eZWorkflowEventType
{
    function RightNowUserType()
    {
    	$this->eZWorkflowEventType( EZ_WORKFLOW_TYPE_RIGHTNOW_USER_ID, ezi18n( 'rightnow/event', 'RightNow CRM Customer Connector' ));
    	$this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }
    function execute( &$process, &$event )
    {
        if ( array_key_exists( 'RIGHTNOW_NO_UPDATE', $GLOBALS ) and $GLOBALS['RIGHTNOW_NO_UPDATE'] )
        {
            return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
        }
        else 
        {
            $parameters = $process->attribute( 'parameter_list' );

            // @TODO when right now not available use cron to deliever data.
            if ( RightNow::storeCustomer( $parameters['object_id'] ) )
            {
                return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
            }
            else 
            {
                return EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT;
            }
        }
    }
}
eZWorkflowEventType::registerType( EZ_WORKFLOW_TYPE_RIGHTNOW_USER_ID, 'rightnowusertype' );
?>