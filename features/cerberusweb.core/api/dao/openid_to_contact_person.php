<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_OpenIdToContactPerson {
	const OPENID_CLAIMED_ID = 'openid_claimed_id';
	const CONTACT_PERSON_ID = 'contact_person_id';
	const HASH_KEY = 'hash_key';
	
	public static function addOpenId($openid_claimed_id, $contact_person_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// [TODO] Check for dupe
		
		$sql = sprintf("INSERT IGNORE INTO openid_to_contact_person (openid_claimed_id, contact_person_id, hash_key) ".
			"VALUES (%s, %d, %s)",
			$db->qstr($openid_claimed_id),
			$contact_person_id,
			$db->qstr(md5($openid_claimed_id))
		);
		$db->ExecuteMaster($sql);
		
		return TRUE;
	}
	
	public static function getOpenIdByHash($hash) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$object = NULL;
		
		$sql = sprintf(
			"SELECT openid_claimed_id, contact_person_id, hash_key FROM openid_to_contact_person WHERE %s = %s",
			self::HASH_KEY,
			$db->qstr($hash)
		);
		$row = $db->GetRowSlave($sql);
		
		if(!empty($row)) {
			$object = new Model_OpenIdToContactPerson();
			$object->openid_claimed_id = $row['openid_claimed_id'];
			$object->contact_person_id = $row['contact_person_id'];
			$object->hash_key = $row['hash_key'];
		}
		
		return $object;
	}
	
	public static function getOpenIdsByContact($contact_person_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$results = array();
		
		$sql = sprintf(
			"SELECT openid_claimed_id FROM openid_to_contact_person WHERE %s = %d",
			self::CONTACT_PERSON_ID,
			$contact_person_id
		);
		$rs = $db->GetArraySlave($sql);
		
		foreach($rs as $row) {
			$results[$row['openid_claimed_id']] = $row['openid_claimed_id'];
		}
		
		return $results;
	}
	
	public static function getContactIdByOpenId($openid_claimed_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf(
			"SELECT contact_person_id FROM openid_to_contact_person WHERE %s = %s",
			self::OPENID_CLAIMED_ID,
			$db->qstr($openid_claimed_id)
		);
		return $db->GetOneSlave($sql);
	}
	
	public static function deleteByContactPerson($contact_person_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM openid_to_contact_person WHERE %s = %d",
			self::CONTACT_PERSON_ID,
			$contact_person_id
		);
		$db->ExecuteMaster($sql);
		
		// Release associated email addresses
		$fields = array(
			DAO_Address::CONTACT_PERSON_ID => 0,
		);
		DAO_Address::updateWhere($fields, sprintf("%s = %d", DAO_Address::CONTACT_PERSON_ID, $contact_person_id));
	}
	
	public static function deleteByOpenId($openid_claimed_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM openid_to_contact_person WHERE %s = %s",
			self::OPENID_CLAIMED_ID,
			$db->qstr($openid_claimed_id)
		);
		$db->ExecuteMaster($sql);
	}
	
	public static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Delete where orphaned contact_person
		$db->ExecuteMaster("DELETE FROM openid_to_contact_person WHERE contact_person_id NOT IN (SELECT id FROM contact_person)");
	}
};

class Model_OpenIdToContactPerson {
	public $openid_claimed_id = '';
	public $contact_person_id = 0;
	public $hash_key = '';
};