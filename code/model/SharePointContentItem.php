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
 
/**
 * Object representing a sharepoint content item
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class SharePointContentItem extends ExternalContentItem
{
	protected $wrappedObject;

	/**
	 * Creates a new content item based on data passed
	 * through from a sharepoint server
	 *
	 * @param SharePointContentSource $source
	 * @param String $id
	 * @param StdClass $content
	 */
	public function __construct($source=null, $id=null, $content=null)
	{
		if ($content) {
			$this->wrappedObject = $content;
			// make up a sharepoint ID based on the structure of a raw sharepoint
			// object
			$id = isset($content[SharePointClient::ID_IDX]) ? $content[SharePointClient::ID_IDX] : ''; 

			if (!$id) {
				throw new Exception ("Cannot create object from invalid content: ".var_export($content, true));
			}
			parent::__construct($source, $id);
		} else {
			if (is_bool($id)) {
				$id = 0;
			}
			parent::__construct($source, $id);
		}
	}

	/**
	 *
	 * @param Object $content
	 *			A stdclass object containing sharepoint data
	 */
	public function init($content = null)
	{
		$repo = $this->source->getRemoteRepository();
		if (!$this->wrappedObject && $this->externalId) {
			$this->wrappedObject = $repo->getObject($this->externalId);
		}

		if ($this->wrappedObject) {
			// For each key/value pair from the remote object,
			// store the data locally
			foreach ($this->wrappedObject as $field => $value) {
				if ($field == 'ID') {
					continue;
				}
				$this->$field = $value;
			}

			$this->Name = $this->Title;
			$this->MenuTitle = $this->Title;
		}
	}

	/**
	 * Retrieve all the children
	 * 
	 * 
	 * @see external-content/code/model/ExternalContentItem#stageChildren($showAll)
	 */
	public function stageChildren()
	{
		$children = new DataObjectSet();
		$id = $this->externalId;
		$folderName = null;

		// if it has an fstype, then check it, otherwise continue
		if (isset($this->FSObjType)) {
			if (!$this->FSObjType) {
				return $children;
			}
			
			$folderName = $this->FileRef;  // substr($this->FileRef, strpos($this->FileRef, '#') + 1);
		}

		$repo = $this->source->getRemoteRepository();
		
		if ($repo->isConnected()) {
			
			$childObjs = $repo->getChildren($id, $folderName);
			if ($childObjs) {
				foreach ($childObjs as $obj) {
					$children->push($this->source->getObject($obj));
				}
			}
		}

		return $children;
	}

	public function numChildren()
	{
		$children = $this->Children();
		return $children->Count();
	}

	public function getType()
	{
		return isset($this->FSObjType) ? ($this->FSObjType ? 'folder' : 'document') : 'folder';
	}

	public function streamContent($toFile='')
	{
		if ($this->getType() == 'document') {
			$this->source->getRemoteRepository()->streamObject($this, $toFile);
		}
	}
}

?>