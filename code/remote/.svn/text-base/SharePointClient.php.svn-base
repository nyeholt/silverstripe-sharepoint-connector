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


include_once dirname(dirname(dirname(__FILE__))).'/thirdparty/nusoap/lib/nusoap.php';

/**
 * A client of a sharepoint server
 * 
 * Implements a subset of features as we're waiting for CMIS... 
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 */
class SharePointClient
{
	const LIST_WSDL = '/_vti_bin/Lists.asmx?wsdl';
	
	const ID_IDX = 'SS_SP_ID';
	
	const SEARCH_QUERY_OPTIONS = '<QueryOptions><ViewAttributes Scope="RecursiveAll" /></QueryOptions>';

	/**
	 * The URL being connected to
	 * 
	 * @var String
	 */
	protected $url;
	
	/**
	 * The user connecting (needed for caching...)
	 * @var unknown_type
	 */
	protected $username;

	/**
	 * Need to store in case someone wants to download through this connection
	 * too.
	 *
	 * @var String
	 */
	protected $password;
	
	/**
	 * Connect the sharepoint client
	 * 
	 * @param String $sharepointUrl
	 * @param String $username
	 * @param String $password
	 */
	public function connect($sharepointUrl, $username, $password)
	{
		$client = $this->getClient($sharepointUrl . self::LIST_WSDL);
		$auth = "$username:$password";
		
		$this->url = $sharepointUrl;
		$this->username = $username;
		$this->password = $password;

		$client->setCredentials("","","ntlm");
		$client->setCurlOption(CURLOPT_USERPWD, $auth);
	}
	
	
	public function isConnected()
	{
		return $this->soapClient != null;
	}
	
	public function disconnect()
	{
		
	}
	
	/**
	 * Gets all of the lists in this share point server
	 * 
	 * @return unknown_type
	 */
	public function getLists()
	{
		$cacheKey = md5($this->url.'|getlists|'.$this->username);
		$data = CacheService::inst()->get($cacheKey);
		
		if (!$data) {
			
			$result = $this->getClient()->call('GetListCollection');
	
			$items = array();
			
			if (isset($result['GetListCollectionResult']['Lists']['List'])) {
		        $lists = $result['GetListCollectionResult']['Lists']['List'];
		        foreach ($lists as $list) {
	                /*$title = $list['!Title'];
	                $viewUrl = $list['!DefaultViewUrl'];
	                $webId = $list['!WebId'];*/
		        	$list = $this->sanitiseObject($list);
		        	// we want to store a special SS_ID here which is a joining
		        	// of the listID with the object id; obviously, lists 
		        	// don't have the object id, but we'll do it to maintain
		        	// consistency
		        	$list[self::ID_IDX] = $list['ID'];
	                $items[$list['ID']] = $list; 
		        }
			}
			
			CacheService::inst()->store($cacheKey, $items);
			return $items;
		}
		
		return $data;
	}
	
	/**
	 * Gets the children of a given object.
	 * 
	 * The ObjectID in this case may be a list id, or a compound of
	 * the list and the item's ID in that list
	 * 
	 * @param $objectId
	 * @return array
	 */
	public function getChildren($objectId, $folderPath = '')
	{
		$cacheKey = md5($this->url.'|getChildren|'.$objectId.'|'.$folderPath.'|'.$this->username);
		$data = CacheService::inst()->get($cacheKey);
		
		if (!is_array($data)) {
			$id = $this->getCompoundId($objectId);
			
			$childQueryArgs = array(
				'listName' => $id->listId
			);
	
			// if it's null ID, just list the top level of the 
			// list
			if ($id->id) {
				$parentQueryOptions = '<QueryOptions><Folder>'.$folderPath.'</Folder></QueryOptions>';
				$childQueryArgs['queryOptions'] = array($parentQueryOptions);
			}
	
			$result = $this->getClient()->call('GetListItems', $childQueryArgs);

			$output = array();
			if (isset($result['GetListItemsResult']['listitems']['data']['row'])) {
				if ($result['GetListItemsResult']['listitems']['data']['!ItemCount'] == '1') {
					$output[] = $this->sanitiseObject($result['GetListItemsResult']['listitems']['data']['row'], $id->listId);
				} else {
					foreach ($result['GetListItemsResult']['listitems']['data']['row'] as $res) {
						$output[] = $this->sanitiseObject($res, $id->listId);
					}
				}
			}

			CacheService::inst()->store($cacheKey, $output);
			return $output;
		} 

		return $data;
	}
	
	/**
	 * Gets an object based on its ID
	 * 
	 * @param String $id
	 * @return array
	 */
	public function getObject($id)
	{
		$cacheKey = md5($this->url.'|getObject|'.$id.'|'.$this->username);
		$data = CacheService::inst()->get($cacheKey);

		if (!$data) {
			$id = $this->getCompoundId($id);
			
			if (!$id->id) {
				// just the list
				$lists = $this->getLists();
				return isset($lists[$id->listId]) ? $lists[$id->listId] : null;
			}
	
			$xml = <<<XML
			<Query>
			   <Where>
			      <Eq>
			         <FieldRef Name="ID" />
			         <Value Type="Counter">$id->id</Value>
			      </Eq>
			   </Where>
			</Query>
XML;
			$searchQueryArgs = array(
				'listName' => $id->listId,
				'queryOptions' => array(self::SEARCH_QUERY_OPTIONS),
				'query' => array($xml),
			);
	
			$result = $this->getClient()->call('GetListItems', $searchQueryArgs);
			
			// This result SHOULD be of the form
			/*Array
				(
				    [GetListItemsResult] => Array
				        (
				            [listitems] => Array
				                (
				                    [data] => Array
				                        (
				                            [row] => Array*/
			$obj = null;
			if (isset($result['GetListItemsResult']['listitems']['data']['row'])) {
				$obj = $this->sanitiseObject($result['GetListItemsResult']['listitems']['data']['row'], $id->listId);
				// set the compound ID
				 
			}
			CacheService::inst()->store($cacheKey, $obj);
			return $obj;
		}

		return $data;
	}
	
	/**
	 * Get a compound ID from the passed in object ID
	 * 
	 * A compound ID has the form 
	 * 
	 * {list-id-guid}:ID
	 * 
	 * where the ID bit is optional
	 * 
	 * @param String $objectId
	 * @return StdClass
	 */
	protected function getCompoundId($objectId)
	{
		$bits = explode(':', $objectId);
		$id = new StdClass;

		if (count($bits) == 2) {
			// compound
			list($id->listId, $id->id) = $bits;
		} else {
			$id->listId = $bits[0];
			$id->id = null;
		}
		
		
		if (!(strpos($id->listId, '{') === 0)) {
			throw new Exception("Passed in ID $objectId is invalid");
		}

		return $id;
	}
	
	/**
	 * Make the object a little more sane
	 */
	protected function sanitiseObject($in, $listId = null)
	{
		$new = array();
		

		foreach ($in as $key => $value) {
			$key = str_replace('!', '', $key);
			$key = preg_replace('/^ows_/', '', $key);
			
			// for some reason, some of the metadata properties have
			// a prefix of ID;#. Not sure why...
			// @TODO Fix this??
			$value = preg_replace('/^(\d+)\;\#/', '', $value);

			if ($key == 'MetaInfo') {
				$rows = explode("\n", $value);
				foreach ($rows as $row) {
					$fname = substr($row, 0, strpos($row, ':'));
					$value = substr($row, strpos($row, ':') + 1);
					if ($value{2} == '|') {
						$value = substr($value, 3);
					}
					$new[$fname] = $value;
				}
			}

			$new[$key] = $value;
		}

		if ($listId) {
			if (!isset($new['ID'])) {
				throw new Exception("Object ".var_export($in, true). ' has an invalid ID');
			}
			$new[self::ID_IDX] = $listId.':'.$new['ID'];
		}

		if (!isset($new['Title']) && isset($new['vti_title'])) {
			$new['Title'] = $new['vti_title'];
		}

		if (!isset($new['Title']) && isset($new['LinkFilename'])) {
			$new['Title'] = $new['LinkFilename'];
		}

		return $new;
	}

	/**
	 *
	 * @param String $fileRef
	 *			The "FileRef" property of an object to stream the content of
	 */
	public function streamObject(SharePointContentItem $object, $toFile='', $contentType = '')
	{
		$contentUrl = $this->url . '/' . str_replace('%2F', '/', rawurlencode($object->FileRef));

		$contentType = HTTP::getMimeType($object->FileRef);

		if (!$contentType) {
			$contentType = 'application/octet-stream';
		}

		$session = curl_init($contentUrl);
		curl_setopt($session, CURLOPT_USERPWD, $this->username.':'.$this->password);
		curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);

		if (!strlen($toFile)) {
			$filename = rawurlencode($object->LinkFilename);
			header("Content-Disposition: atachment; filename=$filename");
			header("Content-Type: $contentType");
			// header("Content-Length: ".filesize("$path/$filename"));
			header("Pragma: no-cache");
			header("Expires: 0");
			curl_exec($session);
		} else {
			// get the file and store it into a local item
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($session);
			$fp = fopen($toFile, 'w');
			if (!$fp) {
				throw new Exception("Could not write file to $toFile");
			}
			fwrite($fp, $response);
			fclose($fp);
		}

		curl_close($session);
	}

	/**
	 * The soap client we're using. 
	 * @var unknown_type
	 */
	protected $soapClient;
	
	/**
	 * Get a soap client for the given wsdl
	 * 
	 * @param String $url
	 * @return nusoap_client
	 */
	protected function getClient($url = '')
	{
		if (!$this->soapClient) {
			$this->soapClient = new nusoap_client($url, true);
		}

		return $this->soapClient;
	}
}

/* SAMPLE CODE ONLY
$searchQueryOptions = <<<XML
<QueryOptions>
	<ViewAttributes Scope="RecursiveAll" />
</QueryOptions>
XML;

$parentQueryOptions = <<<XML
<QueryOptions>
	<Folder>Shared Documents</Folder>
</QueryOptions>
XML;


$query = <<<XML
<Query>
   <Where>
      <Eq>
         <FieldRef Name="ID" />
         <Value Type="Counter">3</Value>
      </Eq>
   </Where>
</Query>
XML;

$childQueryArgs = array(
	'listName' => '1A545C57-1EEB-4B56-9B2D-0425296D6D74',
	'queryOptions' => array($parentQueryOptions),
);

$searchQueryArgs = array(
	'listName' => '1A545C57-1EEB-4B56-9B2D-0425296D6D74',
	'queryOptions' => array($searchQueryOptions),
	'query' => array($query),
);

$result = $client->call('GetListItems', $searchQueryArgs);*/
?>