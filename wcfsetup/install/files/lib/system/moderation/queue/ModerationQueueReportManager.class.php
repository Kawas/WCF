<?php
namespace wcf\system\moderation\queue;
use wcf\data\moderation\queue\ModerationQueue;
use wcf\data\moderation\queue\ModerationQueueAction;
use wcf\data\moderation\queue\ViewableModerationQueue;
use wcf\system\exception\SystemException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Moderation queue implementation for reports.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2015 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf
 * @subpackage	system.moderation.queue
 * @category	Community Framework
 */
class ModerationQueueReportManager extends AbstractModerationQueueManager {
	/**
	 * @see	\wcf\system\moderation\queue\AbstractModerationQueueManager::$definitionName
	 */
	protected $definitionName = 'com.woltlab.wcf.moderation.report';
	
	/**
	 * Returns true if given item was already reported.
	 * 
	 * @param	string		$objectType
	 * @param	integer		$objectID
	 * @return	boolean
	 */
	public function isAlreadyReported($objectType, $objectID) {
		$objectTypeID = $this->getObjectTypeID($objectType);
		
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".WCF_N."_moderation_queue
			WHERE	objectTypeID = ?
				AND objectID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$objectTypeID,
			$objectID
		));
		$row = $statement->fetchArray();
		
		return ($row['count'] == 0 ? false : true);
	}
	
	/**
	 * Returns true if the object with the given data has a pending report.
	 * A pending report has a status other than done.
	 * 
	 * @param	string		$objectType
	 * @param	integer		$objectID
	 * @return	boolean
	 */
	public function hasPendingReport($objectType, $objectID) {
		$objectTypeID = $this->getObjectTypeID($objectType);
		
		$sql = "SELECT	COUNT(*)
			FROM	wcf".WCF_N."_moderation_queue
			WHERE	objectTypeID = ?
				AND objectID = ?
				AND status <> ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$objectTypeID,
			$objectID,
			ModerationQueue::STATUS_DONE
		));
		
		return $statement->fetchColumn() > 0;
	}
	
	/**
	 * Returns true if current user can report given content.
	 * 
	 * @param	string		$objectType
	 * @param	integer		$objectID
	 * @return	boolean
	 */
	public function canReport($objectType, $objectID) {
		return $this->getProcessor($objectType)->canReport($objectID);
	}
	
	/**
	 * @see	\wcf\system\moderation\queue\IModerationQueueManager::getLink()
	 */
	public function getLink($queueID) {
		return LinkHandler::getInstance()->getLink('ModerationReport', array('id' => $queueID));
	}
	
	/**
	 * Returns rendered template for reported content.
	 * 
	 * @param	\wcf\data\moderation\queue\ViewableModerationQueue	$queue
	 * @return	string
	 */
	public function getReportedContent(ViewableModerationQueue $queue) {
		return $this->getProcessor(null, $queue->objectTypeID)->getReportedContent($queue);
	}
	
	/**
	 * Returns the reported object.
	 * 
	 * @param	string		$objectType
	 * @param	integer		$objectID
	 * @return	\wcf\data\IUserContent
	 */
	public function getReportedObject($objectType, $objectID) {
		return $this->getProcessor($objectType)->getReportedObject($objectID);
	}
	
	/**
	 * Adds a report for specified content.
	 * 
	 * @param	string		$objectType
	 * @param	integer		$objectID
	 * @param	string		$message
	 * @param	array		$additionalData
	 */
	public function addReport($objectType, $objectID, $message, array $additionalData = array()) {
		if (!$this->isValid($objectType)) {
			throw new SystemException("Object type '".$objectType."' is not valid for definition 'com.woltlab.wcf.moderation.report'");
		}
		
		$additionalData['message'] = $message;
		$this->addEntry(
			$this->getObjectTypeID($objectType),
			$objectID,
			$this->getProcessor($objectType)->getContainerID($objectID),
			$additionalData
		);
	}
	
	/**
	 * @see	\wcf\system\moderation\queue\AbstractModerationQueueManager::addEntry()
	 */
	protected function addEntry($objectTypeID, $objectID, $containerID = 0, array $additionalData = array()) {
		$sql = "SELECT	queueID
			FROM	wcf".WCF_N."_moderation_queue
			WHERE	objectTypeID = ?
				AND objectID = ?
				AND status <> ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$objectTypeID,
			$objectID,
			ModerationQueue::STATUS_DONE
		));
		$row = $statement->fetchArray();
		
		if ($row === false) {
			$objectAction = new ModerationQueueAction(array(), 'create', array(
				'data' => array(
					'objectTypeID' => $objectTypeID,
					'objectID' => $objectID,
					'containerID' => $containerID,
					'userID' => (WCF::getUser()->userID ?: null),
					'time' => TIME_NOW,
					'additionalData' => serialize($additionalData)
				)
			));
			$objectAction->executeAction();
		}
		else {
			$objectAction = new ModerationQueueAction(array($row['queueID']), 'update', array(
				'data' => array(
					'status' => ModerationQueue::STATUS_OUTSTANDING,
					'containerID' => $containerID,
					'userID' => (WCF::getUser()->userID ?: null),
					'time' => TIME_NOW,
					'additionalData' => serialize($additionalData)
				)
			));
			$objectAction->executeAction();
		}
		
		ModerationQueueManager::getInstance()->resetModerationCount();
	}
}
