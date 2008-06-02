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
            // Wenn RightNow nicht verf�gbar ist per cron wiederholen.
            // Cronjob f�r workflow pr�fen.
            $uID=$_POST["UserID"];
    
    
            if ( RightNow::storeCustomer( $uID ) )
            {
                return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
            }
            else 
            {
                return EZ_WORKFLOW_TYPE_STATUS_DEFERRED_TO_CRON_REPEAT;
            }
    
            return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
        }
        
    }
    
}
eZWorkflowEventType::registerType( EZ_WORKFLOW_TYPE_RIGHTNOW_USER_ID, 'rightnowusertype' );
?>