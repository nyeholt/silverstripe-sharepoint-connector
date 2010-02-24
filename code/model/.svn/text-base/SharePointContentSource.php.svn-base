<?php
/**

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.
 
*/
 

class SharePointContentSource extends ExternalContentSource implements ExternalContentRepositoryProvider
{
	
	public static $db = array(
		'SharePointUrl' => 'Varchar(128)',
		'SharePointUser' => 'Varchar(64)',
		'SharePointPass' => 'Varchar(64)',
		'ListID' => 'Varchar(64)'
	);


	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Main', new TextField('SharePointUrl', _t('SharePointContentSource.SP_URL', 'SharePoint Url')));
		$fields->addFieldToTab('Root.Main', new TextField('SharePointUser', _t('SharePointContentSource.USER', 'Username')));
		$fields->addFieldToTab('Root.Main', new PasswordField('SharePointPass', _t('SharePointContentSource.PASS', 'Password')));
		$fields->addFieldToTab('Root.Main', new TextField('ListID', _t('SharePointContentSource.LIST_ID', 'List ID (Optional, including the enclosing { } characters)')));

		return $fields;
	}
	
	/**
	 * The sharepoint client to use
	 * @var SharePointClient
	 */
	protected $repo;
	
	
	public function getRemoteRepository()
	{
		if (!$this->repo) {
			$this->repo = new SharePointClient();
		}
		
		if (!$this->repo->isConnected()) {
			// connect away
			$this->repo->connect($this->SharePointUrl, $this->SharePointUser, $this->SharePointPass);
		}

		return $this->repo;
	}

	/**
	 * Return a new matrix content importer 
	 * @see external-content/code/dataobjects/ExternalContentSource#getContentImporter()
	 */
	public function getContentImporter($target=null)
	{
		return new SharePointImporter();
	}

	/**
	 * Matrix content can only be imported into 
	 * the sitetree for now. 
	 * 
	 * @see external-content/code/dataobjects/ExternalContentSource#allowedImportTargets()
	 */
	public function allowedImportTargets()
	{
		return array('file' => true);
	}
	

	/**
	 * Whenever we save the content source, we want to disconnect 
	 * the repository so that it reconnects with whatever new connection
	 * details are provided
	 * 
	 * @see sapphire/core/model/DataObject#onBeforeWrite()
	 */
	public function onBeforeWrite()
	{
		parent::onBeforeWrite();
		$repo = $this->getRemoteRepository();
		if ($repo->isConnected()) {
			$repo->disconnect();
		}
	}

	/**
	 * A cache for objects
	 * 
	 * @var array
	 */
	protected $objectCache = array();
	
	/**
	 * Get the object represented by ID
	 * 
	 * @param mixed $object
	 * 			Either an external ID, or a prepulated wrapped object
	 * @return DataObject
	 */
	public function getObject($object)
	{
		$id = null;
		if (is_array($object)) {
			$id = $object[SharePointClient::ID_IDX];
		} else {
			$id = $this->decodeId($object);
		}

		if (!isset($this->objectCache[$id])) {
			// get the object from the repository
			try {
				$item = new SharePointContentItem($this, is_array($object) ? 0 : $id, is_array($object) ? $object : null);
				$this->objectCache[$id] = $item;
			} catch (Exception $e) {
				error_log("Failed creating object : ".$e->getMessage());
				$this->objectCache[$id] = null;
			}
		}

		return isset($this->objectCache[$id]) ? $this->objectCache[$id] : null;
	}

	/**
	 * Get the root node. Only matters if the user has specified a root 
	 * list id
	 * 
	 * @see external-content/code/model/ExternalContentSource#getRoot()
	 */
	public function getRoot()
 	{
 		if ($this->ListID) {
 			// make sure there's a { char
 			if (!strpos($this->ListID, '{') === 0) {
 				$this->ListID = '{'.$this->ListID.'}';
 			}
 			return $this->getObject($this->encodeId($this->ListID));
 		}
 	}

 	/**
 	 * When getting the children of a sharepoint server, we first check
 	 * to see if we have a list ID. If so, we're just going to get it directly,
 	 * then return all if its children
 	 * 
 	 * @see external-content/code/model/ExternalContentSource#stageChildren($showAll)
 	 */
	public function stageChildren($showAll = false)
	{
		$root = $this->getRoot();
		if ($root) {
			return $root->stageChildren();
		}

		// content source is different... we first load all the LIST objects
		// and let them display their children.
		$lists = $this->getRemoteRepository()->getLists();

		$children = new DataObjectSet();
		// go through each and create a new object
		foreach ($lists as $list) {
			if (isset($list[SharePointClient::ID_IDX])) {
				$children->push($this->getObject($list));
			}
		}
		return $children;
	}
}


?>