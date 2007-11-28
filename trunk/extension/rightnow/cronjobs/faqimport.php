<?php
/**
 * File faqimport.php
 *
 * @package rightnow
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
#
# FAQ import from CRM Right Now
#
include_once( 'kernel/classes/ezcontentclass.php');
include_once( 'lib/ezdb/classes/ezdb.php' );
include_once('extension/rightnow/classes/rightnow.php' );
include_once( 'lib/ezutils/classes/ezini.php' );

include_once( 'lib/ezutils/classes/ezextension.php' ); 
ext_class( 'import' , 'ezimportframework' ); 


define('FAQ_DATASET_EXISTS', 'a_id_exists');
define('FAQ_DATASET_NOT_EXISTS', 'a_id_not_exists');
define('FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC', 'a_id_not_public');

define('DATASET_EXISTS', 'dataset_exists');



if ( !$isQuiet )
{
    $cli->output("Starting processing - FAQ Import from RightNow\n");    
}


if ( !is_dir( eZExtension::baseDirectory() . '/import'  ) )
{
	$cli->error( 'No import extension found.' );
    $script->shutdown( 1 );
}

if ( !$isQuiet )
{
    
    $cli->output( 'Using Siteaccess '.$GLOBALS['eZCurrentAccess']['name'] );
    
}

// login as admin
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
$user = eZUser::fetchByName( 'admin' );

if ( is_object( $user ) )
{
	if ( $user->loginCurrent() )
	   $cli->output( "Logged in as 'admin'" );
}
else
{
	$cli->error( 'No admin.' );
    $script->shutdown( 1 );
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
		$a1=RightNow::getFAQById(647);

		$creationResult = RightNow::createAnswer($answerArr);
	*/	
		
		
// use bay workflow - it it set true - no rightnow answer will be created
$GLOBALS['RIGHTNOW_NO_UPDATE'] = true;
	

// get Data needed for import

//$classIdentifier = "faq";
//$containerNodeId = 374;

// get DATA from INI
$rightNowIni =& eZINI::instance( 'rightnow.ini' );
$faqClassIdentifier = $rightNowIni->variable( 'FAQImportSettings', 'FAQClassIdentifier' );
$faqContainerNodeId = $rightNowIni->variable( 'FAQImportSettings', 'FAQContainerNodeId' );

$categoryClassIdentifier = $rightNowIni->variable( 'FAQImportSettings', 'CategoryClassIdentifier' );
$categoryContainerNodeId = $rightNowIni->variable( 'FAQImportSettings', 'CategoryContainerNodeId' );

$topicClassIdentifier = $rightNowIni->variable( 'FAQImportSettings', 'TopicClassIdentifier' );
$topicContainerNodeId = $rightNowIni->variable( 'FAQImportSettings', 'TopicContainerNodeId' );

//* CHECK IF ALL IS OK *//
if ( !is_object( eZContentObjectTreeNode::fetch( $faqContainerNodeId ) ) )
{
    $cli->output( "No FAQContainerNodeId" );
    exit();
}
if ( !is_object( eZContentObjectTreeNode::fetch( $categoryContainerNodeId ) ) )
{
    $cli->output( "No CategoryContainerNodeId" );
    exit();
}
if ( !is_object( eZContentObjectTreeNode::fetch( $topicContainerNodeId ) ) )
{
    $cli->output( "No TopicContainerNodeId" );
    exit();
}
if ( !is_object( eZContentClass::fetchByIdentifier( $faqClassIdentifier ) ) )
{
    $cli->output( "No FAQClassIdentifier" );
    exit();
}
if ( !is_object( eZContentClass::fetchByIdentifier( $categoryClassIdentifier ) ) )
{
    $cli->output( "No CategoryClassIdentifier" );
    exit();
}
if ( !is_object( eZContentClass::fetchByIdentifier( $topicClassIdentifier ) ) )
{
    $cli->output( "No TopicClassIdentifier" );
    exit();
}
// get alle category from right now
$rightNowSearchCategoryViewId=$rightNowIni->variable( 'FAQImportSettings', 'SearchCategoryViewId' );
$categories = RightNow::getSearch( $rightNowSearchCategoryViewId );

// get all topix from right now
$rightNowSearchTopicViewId=$rightNowIni->variable( 'FAQImportSettings', 'SearchTopicViewId' );
$topics = RightNow::getSearch( $rightNowSearchTopicViewId );

// get all answers from right now
$rightNowSearchAnswerViewId=$rightNowIni->variable( 'FAQImportSettings', 'SearchAnswerViewId' );
$searchResult2 =  RightNow::getSearch( $rightNowSearchAnswerViewId );


// - creating folder for category and topics
// - creating categorys
// - creating topics
// - crating faqs


$searchResult = mergeSameAnswers( $searchResult2 );


// check search Result for double entitys e.g. a answer can have several category
// in the searchresult  every category - answer combination is one row
// merge all answers with the same a_id => category_array('cat 1', 'cat 2');





importCategories($cli, $categories, $categoryClassIdentifier, $categoryContainerNodeId);

importTopics($cli, $topics, $topicClassIdentifier, $topicContainerNodeId);

// get list with Contentobject_ids for object_relation in faq
$category_list = getList( $categoryContainerNodeId, $categoryClassIdentifier );
$topic_list = getList( $topicContainerNodeId, $topicClassIdentifier );

importFAQs($cli, $searchResult['answers'], $faqClassIdentifier, $faqContainerNodeId, $category_list, $topic_list );





// --------------------------------------------------------------
// ------------------- import functions -------------------------
// --------------------------------------------------------------


function importCategories( $cli,  $category_array, $class_identifier, $parent_node_id )
{
	$cli->output( "+++ START Import FAQ Categories +++ Count =  ". count($category_array) );	
	$cli->output( "+++ INI: FAQCategoryContainer: ".$parent_node_id." FAQCategoryClassIdentifier: ".$class_identifier );
	

	$searchResult = $category_array;
	$containerNodeId = $parent_node_id;
	$classIdentifier = $class_identifier;

	/* 4. Setting all Data into Import Framework and process import
	=================================================================*/

	
	$iframework = eZImportFramework::instance( 'default' ); 
	$user = eZUser::fetchByName("admin"); 
	$userID = $user->attribute( 'contentobject_id' ); 
	
	//$class = eZContentClass::fetchByIdentifier( "faq" ); 
	//$containerNodeId = 374;
	
	$namespace="RightNowFAQCategory";
	
	$class = eZContentClass::fetchByIdentifier( $classIdentifier ); 
	
	$options = array ( 	'contentClassID' => $class->attribute( 'id' ), 
						EZ_IMPORT_PRESERVED_KEY_OWNER_ID => $userID, 
						'parentNodeID' => $containerNodeId );
	
	
	$category_count = count( $searchResult );					
	for( $i=1; $i<=$category_count; $i++)
	{	
		
		$name = $searchResult[$i]['1'];
		$cat = getFramworkDataSet( $searchResult[$i] , $containerNodeId, $classIdentifier, $namespace );
		
		if( $cat != false )
		{
			
			if( $cat == DATASET_EXISTS )
			{
				$cli->output( "[Exists] Category = ". $name );
			}
			else 
			{			
				$iframework->getData( array( $cat ), $namespace );
				$return = $iframework->importData( 'ezcontentobject', $namespace, $options ); 
				$cli->output( "[Import] Category = ". $name );
			}
		}
		else 
		{
			$cli->output( "[Fail Import] Category = ". $name );
		}
		
	}
						
						
						
	$iframework->destroy();						
						
	return;
}

function importTopics( $cli,  $topic_array, $class_identifier, $parent_node_id )
{
	
	$cli->output( "+++ START Import Topics +++ Count =  ". count($topic_array) );
	$cli->output( "+++ INI: FAQTopicContainer: ".$parent_node_id." FAQTopicClassIdentifier: ".$class_identifier );
	
	$searchResult = $topic_array;
	$containerNodeId = $parent_node_id;
	$classIdentifier = $class_identifier;

	/* 4. Setting all Data into Import Framework and process import
	=================================================================*/

	
	$iframework = eZImportFramework::instance( 'default' ); 
	$user = eZUser::fetchByName("admin"); 
	$userID = $user->attribute( 'contentobject_id' ); 
	
	//$class = eZContentClass::fetchByIdentifier( "faq" ); 
	//$containerNodeId = 374;
	
	$namespace="RightNowFAQTopic";
	
	$class = eZContentClass::fetchByIdentifier( $classIdentifier ); 
	
	
	
	$options = array ( 	'contentClassID' => $class->attribute( 'id' ), 
						EZ_IMPORT_PRESERVED_KEY_OWNER_ID => $userID, 
						'parentNodeID' => $containerNodeId );
	
	
	$count = count( $searchResult );					
	for( $i=1; $i<=$count; $i++)
	{	
		
		$name = $searchResult[$i]['1'];
		$cat = getFramworkDataSet( $searchResult[$i] , $containerNodeId, $classIdentifier, $namespace );
		
		if( $cat != false )
		{
			
			if( $cat == DATASET_EXISTS )
			{
				$cli->output( "[Exists] Topic = ". $name );
			}
			else 
			{			
				$iframework->getData( array( $cat ), $namespace );
				$return = $iframework->importData( 'ezcontentobject', $namespace, $options ); 
				$cli->output( "[Import] Topic = ". $name );
			}
		}
		else 
		{
			$cli->output( "[Fail Import] Topic = ". $name );
		}
		
	}
						
						
						
	$iframework->destroy();						
						
	return;
}




function importFAQs( $cli, $faq_array , $class_identifier, $parent_node_id, $category_list, $topic_list)
{
	
	
	// get all Category
	
	
	
	// get all Topics
	
	
	$searchResult = $faq_array;
	$containerNodeId = $parent_node_id;
	$classIdentifier = $class_identifier;

	/* 4. Setting all Data into Import Framework and process import
	=================================================================*/
		
	$maxFaqNumber = RightNow::getAllMaxIdFAQ();
	$faqArray = array();
		
	
	
	
	$iframework = eZImportFramework::instance( 'default' ); 
	$user = eZUser::fetchByName("admin"); 
	$userID = $user->attribute( 'contentobject_id' ); 
	
	//$class = eZContentClass::fetchByIdentifier( "faq" ); 
	//$containerNodeId = 374;
	
	$namespace="RightNowFAQ";
	
	$class = eZContentClass::fetchByIdentifier( $classIdentifier ); 
	
	$options = array ( 	'contentClassID' => $class->attribute( 'id' ), 
						EZ_IMPORT_PRESERVED_KEY_OWNER_ID => $userID, 
						'parentNodeID' => $containerNodeId );
	
	//foreach start
	
	
	
	$startTimeStamp = time();
	
	if ( !$isQuiet )
	{
	  	$cli->output( "+++ START: Trying to import ".$maxFaqNumber." Answers from RightNow +++ " );
	  	$cli->output( "+++ INI: FAQContainer: ".$containerNodeId." FAQClassIdentifier: ".$classIdentifier );
	}
	
	//$maxFaqNumber=16;
	
	//for ($aId=0; $aId <= $maxFaqNumber; $aId++)
	//{
//	$maxFaqNumber = 7;
	
	for( $i=1; $i<=$maxFaqNumber; $i++)
	{
		
		$aId = $i;
		//$dataset = getFramworkDataSetByAnserId( $aId , $containerNodeId, $classIdentifier );
		
		$object = getContentObjectByAnswerId( $aId , $containerNodeId, $classIdentifier );
		
		
		if( array_key_exists( $aId, $faq_array ) )
		{
			$a_object = $faq_array[$aId];
			$dataset = getFramworkDataSetFromSearchResult( $a_object , $containerNodeId, $classIdentifier, $category_list, $topic_list );
		}
		else 
		{
			$dataset = FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC;
		}
		
		//$aId = $a_object[1];
		
		if( is_array($dataset) )
			$aId = $dataset['a_id'];
		
		// setting data reay for import
		// data[0] first, data[1] second ...
		//$iframework->data[0] = $dataset;
		if( is_array($dataset) )
		{
			$iframework->getData( array( $dataset ), $namespace );
			$iframework->importData( 'ezcontentobject', $namespace, $options ); 
			if ( !$isQuiet )
			{
	    		$cli->output( "[Importing] AnswerId = $aId" );
			}
			
			if( is_object( $object ))
				unhideObject( $object );
			
			
			
			
		}
		else 
		{
			
			

			
			if( $dataset == FAQ_DATASET_EXISTS )
			{
				$iframework->log("[Exists] RightNow AnswerId: $aId already exist and was not changed");
				if ( !$isQuiet )
				{
		    		$cli->output( "[Exists] AnswerId = $aId already exist and was not changed" );
				}
				
				if( is_object( $object ))
					unhideObject( $object );
			}
			else
			{

				$showSkipMessage=true;
				
				
				if ( $showSkipMessage == true && $dataset == FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC )
				{
					$iframework->log( "[Skip] RightNow AnswerId: $aId not public" );
					if ( !$isQuiet )
					{
		    			
							$cli->output( "[Skip] RightNow AnswerId: $aId not public" );
					}
				}
			
				
				
				// DELETE ContentObjekt if it is deleted in RightNow
				// if is not public only hide
				//=========================================================
					
			//	$object = getContentObjectByAnswerId( $aId , $containerNodeId, $classIdentifier );
				if( is_object($object) )
				{
					
					// check again if aId is deleting or only set to non public
					
					$aIdArr = RightNow::getFAQById($aId);
					
					
					if( count($aIdArr)>0 )
					{
						$main_node_id = $object->attribute('main_node_id');
						$node = eZContentObjectTreeNode::fetch( $main_node_id );					
						
						if( is_object($node) )
						{				
						
							$nodeInvisible = $node->attribute( 'is_invisible' );
							if(  !$nodeInvisible )
							{								
								eZContentObjectTreeNode::hideSubTree( $node );
								$log_message = "[Hide] ContentObject ObjectId: ". $object->attribute('id') ." AnswerId: $aId MeinNodeid= $main_node_id" ;
								
							}						
							
						}
							
						unset($node);
					}
					else 
					{
					// Deleting					
						
						$log_message = "[Deleting] ContentObject ObjectId: ". $object->attribute('id') ." AnserId: $aId " ;
					
						// deleting the node and contentobject	
						$deleteIDArray = array( $object->attribute( 'main_node_id' ) );
		    			eZContentObjectTreeNode::removeSubtrees( $deleteIDArray, $moveToTrash=false );
					}
				
				}
				else
				{
					//if( $showSkipMessage )
					//	$log_message = "-------- ignoring - ContentObject or RightNow AnswerId: $aId not exist" ;
					
					
				}
				
				
				if( $log_message != "")
				{
				
					$iframework->log($log_message);
					if ( !$isQuiet )
					{
			    		$cli->output( $log_message );
					}
				}
			}
			
		}
		
		unset($aId);
		
		$iframework->freeMem();
	}
	
	//foreach end
	
	$iframework->destroy();
	//include_once( 'kernel/classes/ezcache.php' );
	//eZCache::clearAll();
	
	$endTimeStamp = time();

	if ( !$isQuiet )
	{
	    $runningTime = $endTimeStamp - $startTimeStamp;
	    	
		$cli->output( "Import Done - takes $runningTime s" );
		
		$cli->output( "API takes ". (int)$GLOBALS['eZRightNowTime'] . " s" );
		
		$iframework->log("Import Done - takes $runningTime s");
	}
	
	
}





// =====================================================================================
// =================================  help functions ===================================
// =====================================================================================

function unhideObject( $object  )
{
	global $cli;
	
	if( is_object($object) )
	{
			// make node visible
			$node = eZContentObjectTreeNode::fetch( $object->attribute('main_node_id'));
			
			if( is_object($node))
			{
				$nodeInvisible = $node->attribute( 'is_invisible' );
				if(  $nodeInvisible )
				{
					
					eZContentObjectTreeNode::unhideSubTree($node);
					
					
					$logMessage = "[UNHIDE] Node_id = ". $object->attribute('main_node_id') . " object_id = " . $object->attribute('id');
					$cli->output( $logMessage );
					//$iframework->log( $logMessage );
				
					return true;
				}
				
			}
	}
	
	return false;
}


/* Example of RightNow Answer Dataset

    [3] => Array
        (
            [a_id] => 4
            [access_mask] => 0000000001
            [assgn_acct_id] => 59
            [assgn_group_id] => 13
            [created] => 1127724229
            [description] => <as-html>I have a suggestion. Where can I send it?<br /></as-html>
            [lang_id] => 5
            [last_access] => 1162693040
            [last_edited_by] => 1
            [m_id] => 4
            [rule_state] => 64
            [solution] => <as-html>We welcome your comments and suggestions about ways to improve our service. Please visit our <a href="http://www.example.com">feedback section</a>.<br /><br /><br /><br /></as-html>
            [solved_count] => 13
            [source] => 10
            [status_id] => 4
            [status_type] => 4
            [summary] => I have a suggestion. Where can I send it?
            [updated] => 1157472368
        )

*/

/*
get dataset to import topics or categorys
*/
function getFramworkDataSet( $search_result , $cointainer_node_id, $class_identifier, $namespace )
{
	$importingMethod = EZ_IMPORT_METHOD_NO_UPDATE;
	
	// get information from rightnow view search
	$result = array( 'name'	=> $search_result[1]);
	
	// filter wrong values
	if( $result['name'] == 'No Value' 
//		||    $result['name'] == 'multiple'
	    )
	{

		return false;
	}
	
	
	$name_conf = new eZImportConverter( $result['name'] );
	$name_conf->addFilter( "plaintext");
	
	$remote_id = $result['name'];
	
	
	$full_remote_id = "ezimport:".$namespace.":".$remote_id;
	// check if a content object with the remoteid is existiong
	$objectExists = eZContentObject::fetchByRemoteID($full_remote_id);
	
	if( $objectExists )
	{
		return DATASET_EXISTS;
	}
	else 
	{
	
		$dataset = array(	'name' 		=> $name_conf,
					//		EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP => $result['created'],
					//		EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP => $result['updated'],
							EZ_IMPORT_PRESERVED_KEY_REMOTE_ID => $remote_id, 
							EZ_IMPORT_METHOD => $importingMethod									
		);
		
		return $dataset;
	}
					 
}


/**
 * @param array $search_result Result row from RightNowSearch 
 * @return a dataset for the importFramwork
 */
function getFramworkDataSetFromSearchResult( $search_result , $cointainer_node_id, $class_identifier, $category_list, $topic_list)
{
		
	// get information from rightnow view search
	$result = array( 'a_id' 		=> $search_result[1],
					 'summary'  	=> $search_result[2],
					 'description'  => $search_result[3],
					 'solution'  	=> $search_result[4],
					 'status' 		=> $search_result[5],
					 'user' 		=> $search_result[6],
					 'updated' 		=> $search_result[7],				
					 'created'		=> $search_result[8],
					 'category'		=> $search_result[9],
					 'topic'		=> $search_result[10]);
					 
	$a_id = $result['a_id'];
	
	/* 1. get Data to import 
	=================================================*/
	// get a single faq directly from rightnow
	//$result = RightNow::getFAQById( $a_id );
	
	
	// check if contentobject with a_id exists
	
	$importingMethod = EZ_IMPORT_METHOD_NO_UPDATE;

	
	$existingContentObject = getContentObjectByAnswerId( $a_id, $cointainer_node_id, $class_identifier);
	if( is_object($existingContentObject) )
	{
		
		
		// publish only public Answers !!
		if( $result['status'] != "Public" )
		{
		
			return FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC;
		}
			
		
		if( $existingContentObject->attribute('modified') == $result['updated'] )
		{
			// skipping Dataset
			return FAQ_DATASET_EXISTS;
		}
	/*	// publish only public Answers !!
		eleif( $result['status'] == "Public" )
		{
			$importingMethod = EZ_IMPORT_METHOD_UPDATE;	
		}
		else
		{ 
			return FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC;
		}*/
	}
	
	
	//$objectList = getAnswerIdStatus(3, $containerNodeId, 'faq');
	
	if( !$result )
	{
		return FAQ_DATASET_NOT_EXISTS;
	}
	// RighrNow Status id 4 == public
	elseif( $result['status'] == "Public" )  
	{
		$importingMethod = EZ_IMPORT_METHOD_UPDATE;	
	}
	else
	{ 
		return FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC;
	}
	
	
	/* 2. set Inputfilter to convert data before setting in ez content object
	===========================================================================*/
	

	$summary_conf = new eZImportConverter( $result['summary'] );
	$summary_conf->addFilter( "plaintext");
	
	$description_conf = new eZImportConverter( $result['description'] );
	$description_conf->addFilter( "answer");
	$description_conf->addFilter( "htmlparser");
	
	$solution_conf = new eZImportConverter( $result['solution'] );
	$solution_conf->addFilter( "answer");
	$solution_conf->addFilter( "htmlparser");
	
	$category = implode(',',$result['category']);
	$topic = implode(',',$result['topic']);
	
	/* 3. generate data array with ezattributes as key 
	=============================================================================
	e.g. $data['summary'] point to contentclass_attribute 'summary'
	*/
	
	$related_faq_categories = array();
	
	// get contentobject_ids by category_name
	foreach ( $result['category'] as $key )
	{
		if( array_key_exists( $key , $category_list ))
		{
			array_push( $related_faq_categories, $category_list[ $key ] );
		}
	}
	
	$related_faq_topics = array();
	// get contentobject_ids by topic_name
	foreach ( $result['topic'] as $key )
	{
		if( array_key_exists( $key , $topic_list ))
		{
			array_push( $related_faq_topics, $topic_list[ $key ] );
		}
	}
	
	
	$dataset = array(	'summary' 		=> $summary_conf,
						'description'	=> $description_conf,
						'solution'		=> $solution_conf,
						'a_id'			=> (int) $result['a_id'],
					//	'category'		=> $category,	
					//	'topic'		=> $topic,
						'related_faq_categories'	=> $related_faq_categories,	
						'related_faq_topics'		=> $related_faq_topics,
						EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP => $result['created'],
						EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP => $result['updated'],
						EZ_IMPORT_PRESERVED_KEY_REMOTE_ID => (int) $result['a_id'], 
						EZ_IMPORT_METHOD => $importingMethod									
	);
	
	return $dataset;
}


/*
@return array with name as key and content_object_id as value
e.g. array('About Us' => 1234, 'contact' => 1113)
*/
function getList( $parent_node_id, $class_identifier )
{
	$list = array();
	    
	$class_filter_type = 'include';
	$class_filter_array = array( $class_identifier );
	

	$treeParameters = array(             	
                                 	'ClassFilterType' => $class_filter_type,
                                 	'ClassFilterArray' => $class_filter_array                                 
                                  );
       
  
  
    $children =& eZContentObjectTreeNode::subTree( $treeParameters,
                                                           $parent_node_id );
	
	
	foreach ($children as $child)
	{
		$key = $child->attribute('name');
		$objectId = $child->attribute('contentobject_id');
		$list[$key] = $objectId;
	}
                                                           
	
	return $list;
}

/**
 * @param number $a_id RightNow Answer Id
 * @return a dataset for the importFramwork
 */
function getFramworkDataSetByAnserId( $a_id , $cointainer_node_id, $class_identifier)
{
	/* 1. get Data to import 
	=================================================*/
	$result = RightNow::getFAQById( $a_id );
	
	
	// check if contentobject with a_id exists
	
	$importingMethod = EZ_IMPORT_METHOD_NO_UPDATE;

	
	$existingContentObject = getContentObjectByAnswerId( $a_id, $cointainer_node_id, $class_identifier);
	if( is_object($existingContentObject) )
	{
		if( $existingContentObject->attribute('modified') == $result['updated'] )
		{
			// skipping Dataset
			return FAQ_DATASET_EXISTS;
		}
	/*	// publish only public Answers !!
		elseif( (int) $result['status_id'] == 5 )
		{
			$importingMethod = EZ_IMPORT_METHOD_UPDATE;	
		}
		else
		{ 
			return FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC;
		}*/
	}
	
	
	//$objectList = getAnswerIdStatus(3, $containerNodeId, 'faq');
	
	if( !$result )
	{
		return FAQ_DATASET_NOT_EXISTS;
	}
	// RighrNow Status id 4 == public
	elseif( (int) $result['status_id'] == 4 )  
	{
		$importingMethod = EZ_IMPORT_METHOD_UPDATE;	
	}
	else
	{ 
		return FAQ_DATASET_EXISTS_BUT_NOT_PUBLIC;
	}
	
	
	/* 2. set Inputfilter to convert data before setting in ez content object
	===========================================================================*/
	
	/*$html= "antwort der frage";
	$conv = new eZImportConverter( $html );
	$conv->addFilter( "plaintext");
	$conv->addFilter( "plaintext33");
	*/
	
	$summary_conf = new eZImportConverter( $result['summary'] );
	$summary_conf->addFilter( "plaintext");
	
	$description_conf = new eZImportConverter( $result['description'] );
	$description_conf->addFilter( "plaintext");
	
	$solution_conf = new eZImportConverter( $result['solution'] );
	$solution_conf->addFilter( "plaintext");
	
	$category = 'Default Category';
	
	/* 3. generate data array with ezattributes as key 
	=============================================================================
	e.g. $data['summary'] point to contentclass_attribute 'summary'
	*/
	
	$dataset = array(	'summary' 		=> $summary_conf,
						'description'	=> $description_conf,
						'solution'		=> $solution_conf,
						'a_id'			=> (int) $result['a_id'],
						'category'		=> $category,	
						'keywords'		=> $result['keywords'],
						EZ_IMPORT_PRESERVED_KEY_CREATION_TIMESTAMP => $result['created'],
						EZ_IMPORT_PRESERVED_KEY_MODIFICATION_TIMESTAMP => $result['updated'],
						EZ_IMPORT_PRESERVED_KEY_REMOTE_ID => (int) $result['a_id'], 
						EZ_IMPORT_METHOD => $importingMethod									
	);
	
	return $dataset;
}

/**
* @return ContentObject with given a_id (AnswerId)
*/
function getContentObjectByAnswerId($a_id, $cointainer_node_id, $class_identifier)
{
	include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
	
	$remote_id = 'ezimport:RightNowFAQ:'.$a_id;
	
	$object = eZContentObject::fetchByRemoteID($remote_id);
	

	if( is_object($object))
		return $object;
	else 
		return false;
	
/*	$parentNodeID = $cointainer_node_id;

	$attribute_filter=array( array( "$class_identifier/a_id",  
									'=',
                                    $a_id ) );
	
	$class_filter_type = 'include';
	$class_filter_array = array( $class_identifier );
	

	$treeParameters = array( 'AttributeFilter' => $attribute_filter,                               	
                                 	'ClassFilterType' => $class_filter_type,
                                 	'ClassFilterArray' => $class_filter_array                                 
                                  );
       
  
  
    $children =& eZContentObjectTreeNode::subTree( $treeParameters,
                                                           $parentNodeID );
    
	
                                                                       
	if( count($children) >= 1 )
	{
		$result = $children[0]->attribute('object');
		return $result;
		
	}
	else
	{
		return false;
	}
	*/
  /*  if ( $children === null )
    {
            $result = array( 'error' => array( 'error_type' => 'kernel',
                                               'error_code' => EZ_ERROR_KERNEL_NOT_FOUND ) );
    }*/
 

  
}




function mergeSameAnswers( $search_result_array )
{
	$result_arr = array();
	
	$categories = array();
	$topics = array();
	
	foreach ($search_result_array as $row)
	{
		
		$a_id = $row[1];
		
		
		
		
		// if a_id already in array append $topics and category
		if( array_key_exists($a_id, $result_arr) )
		{
			$item = $result_arr[$a_id];
			
			//$category_arr=$item[9];
			array_push( $item[9], $row[9] );	
			$item[9] = array_unique( $item[9] );	
					
			//$topic_arr=$item[10];
			array_push( $item[10], $row[10]);	
			$item[10] = array_unique( $item[10] );		
			
		}		
		else 
		{
			$item = $row;
			// if a_id not in array
			
			$category_arr=array();
			$topic_arr=array();
			array_push( $category_arr, $row[9]);		
			$item[9]=$category_arr;
			
			
			array_push( $topic_arr, $row[10]);		
			$item[10]=$topic_arr;
			
			
			
		}
		
		$result_arr[$a_id] = $item;
		
		array_push($categories, $row[9]);
		array_push($topics, $row[10]);
		
		// if a_id in array append category and topic
		// categoryarry und topic array zusammenfassen bei gleichen eintr�gen
	}
	
	$categories = array_unique($categories);
	$topics = array_unique($topic_arr);
	
	return array( 'answers' => $result_arr , 'categories' => $categories, 'topics' => $topics);
}


?>
