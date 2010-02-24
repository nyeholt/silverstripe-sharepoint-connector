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
 * A simple read-only sharepoint connector test
 * 
 * This assumes you have a sharepoint instance running on 
 * 
 * URL 
 * 
 * and a relevant username and pass. 
 * 
 * 
 *  
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class SharePointConnectorTest extends SapphireTest
{
	const URL = "http://10.1.1.9";
	const USER = 'Administrator';
	const PASS = 'Admin!@#$';
	
	protected $items;

	function testGetLists()
	{
		$conn = new SharePointClient();
		$conn->connect(self::URL, self::USER, self::PASS);

		$this->items = $conn->getLists();

		$this->assertTrue(is_array($this->items));
		$this->assertTrue(count($this->items) > 0);
	}
	
	function testGetRootDocuments()
	{
		$conn = new SharePointClient();
		$conn->connect(self::URL, self::USER, self::PASS);
		
		$id = '{1A545C57-1EEB-4B56-9B2D-0425296D6D74}:3';

		$item = $conn->getObject($id);
		
		$this->assertNotNull($item);
	}
	
	function testGetChildren()
	{
		$conn = new SharePointClient();
		$conn->connect(self::URL, self::USER, self::PASS);
		$id = '{1A545C57-1EEB-4B56-9B2D-0425296D6D74}';
		$res = $conn->getChildren($id);
		
		print_r($res);
	}
}

?>