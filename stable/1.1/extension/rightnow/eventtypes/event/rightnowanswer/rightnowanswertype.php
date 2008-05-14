<?php
/**
 * File containing the RightNowAnswerType class.
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
/*
	1. nach dem anlegen einer neuen FAQ in EZ wird in right now eine neue answer erstellt
		+ ez object auf <hidden> gestellt
		
	2. wenn in ez ein contentobject mit bestehender answerid (rightnow) bearbeitet wird
		+ rightnow wird nicht geupdatet
		+ ez object auf <hidden> gestellt

	3. wennn global flag $GLOBALS['RIGHTNOW_NO_UPDATE'] gesetzt nix passiert

*/

define( "EZ_WORKFLOW_TYPE_RIGHTNOW_ANSWER_ID", "rightnowanswer" );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath(__FILE__) . '/classes/rightnow.php' );

class RightNowAnswerType extends eZWorkflowEventType
{
    function RightNowAnswerType()
    {
    	$this->eZWorkflowEventType( EZ_WORKFLOW_TYPE_RIGHTNOW_ANSWER_ID, ezi18n( 'rightnow/event', 'RightNow CRM Answer Creation' ));
    	$this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }
    function execute( &$process, &$event )
    {
    	if ( $GLOBALS['RIGHTNOW_NO_UPDATE'] )
        {
            return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
        }
        else
        {
            // Wenn RightNow nicht verfügbar ist per cron wiederholen.
            // Cronjob für workflow prüfen.
            $objectID = $GLOBALS['params'][0];   	

	    	$contentObject = eZContentObject::fetch( $objectID );
	    	
	    	if( is_object($contentObject) )
	    	{
	    	$mainNodeId = $contentObject->attribute('main_node_id');
	    	// Hide node
	    	$node = eZContentObjectTreeNode::fetch($mainNodeId);
	    	eZContentObjectTreeNode::hideSubTree( $node );
	    	$dataMap = $contentObject->dataMap();

	    	$version = $contentObject->attribute( "current_version" );
	    	
	    	// 1. version -> create new object in rightnow otherwise do nothing
	    	if( $version > 1)
	    		 return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
	    	
	    		 $rightNowIni =& eZINI::instance( 'rightnow.ini' );
				 $access_mask = $rightNowIni->variable( 'RightNowAnswerCreateSettings', 'access_mask' );
				 $assgn_acct_id = $rightNowIni->variable( 'RightNowAnswerCreateSettings', 'assgn_acct_id' );
    			 $assgn_group_id = $rightNowIni->variable( 'RightNowAnswerCreateSettings', 'assgn_group_id' );
    			 
    			 $status_id = $rightNowIni->variable( 'RightNowAnswerCreateSettings', 'status_id' );
    			 $lang_id = $rightNowIni->variable( 'RightNowAnswerCreateSettings', 'lang_id' );
	    		 
	    		 
	    	
		    	// create new RightNow User
		    	$answerArr['access_mask']=(string)$access_mask;		
				$answerArr['assgn_acct_id']=(int)$assgn_acct_id;
				$answerArr['assgn_group_id']=(int)$assgn_group_id;
				$answerArr['status_id']=(int)$status_id;   //  public(4), private(5), proposed(6), review (7)
			//	$answerArr['keywords']=(string)$dataMap['keywords']->content();
				$answerArr['lang_id']=(int)$lang_id;
				
		 		$answerArr['description']=(string)$dataMap['description']->content();
				$answerArr['summary']=(string)$dataMap['summary']->content();
				$answerArr['solution']=(string)$dataMap['solution']->content();

				$creationResultAID = RightNow::createAnswer($answerArr);
	    	
	    	}
	    	else
	    	{ 
	    		$creationResultAID = -1;
	    	}

            if ( (int) $creationResultAID > 0 )
            {
				$contentObject->setAttribute('remote_id', 'ezimport:RightNowFAQ:'.$creationResultAID );
				$contentObject->store();

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
eZWorkflowEventType::registerType( EZ_WORKFLOW_TYPE_RIGHTNOW_ANSWER_ID, 'rightnowanswertype' );
?>
