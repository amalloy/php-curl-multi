<?php
/**
 * This software is licensed under the MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author Keith Minkler <kminkler@gmail.com>
 * @package Curl
 * @copyright Copyright (c) 2010 Keith Minkler
 */

require(dirname(__FILE__) . "/../Curl/Multi.php");

/**
 * Tests for Curl_Multi class
 */
class Curl_Multi_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that adding and removing a curl handle works appropriately
	 */
	public function testAddRemoveHandle()
	{
		$callback = create_function('$info,$data,$udata', '$udata->fail("Should not have called callback");');
		$c = new Curl_Multi();

		$ch = curl_init("http://www.example.com/");

		$this->assertTrue($c->addHandle($ch, $callback, $this));

		$this->assertFalse($c->removeHandle(-1)); // non-existant handle

		$this->assertTrue($c->removeHandle($ch));

		// make sure handle is no longer used in curl request!
		// callback will trigger test failure if curl request is made

		$this->assertTrue($c->finish());

		curl_close($ch);
	}

	/**
	 * Test negative cases for addHandle
	 */
	public function testBadAddHandleCalls()
	{
		$c = new Curl_Multi();

		$bad_curl_resource = imagecreate(1,1);

		try
		{
			$c->addHandle($bad_curl_resource, create_function('',''), NULL);
			$this->fail("Should have thrown exception on bad curl handle");
		}
		catch(PHPUnit_Framework_AssertionFailedError $e)
		{
			throw $e;
		}
		catch(Exception $e)
		{
			// Okay
		}

		imagedestroy($bad_curl_resource);

		try
		{
			$c->addHandle(NULL, create_function('',''), NULL);
			$this->fail("Should have thrown exception on bad (NULL) curl handle");
		}
		catch(PHPUnit_Framework_AssertionFailedError $e)
		{
			throw $e;
		}
		catch(Exception $e)
		{
			// Okay
		}

		$ch = curl_init('http://www.example.com/');
		try
		{
			$c->addHandle($ch, NULL, NULL);
			$this->fail("Should have thrown exception on bad callback function");
		}
		catch(PHPUnit_Framework_AssertionFailedError $e)
		{
			throw $e;
		}
		catch(Exception $e)
		{
			// Okay
		}

		try
		{
			$c->addHandle($ch, 'im_not_callable', NULL);
			$this->fail("Should have thrown exception on bad callback function");
		}
		catch(PHPUnit_Framework_AssertionFailedError $e)
		{
			throw $e;
		}
		catch(Exception $e)
		{
			// Okay
		}
		curl_close($ch);
	}

	/**
	 * data for this test
	 * @var string $_poll_data
	 */
	public $_poll_data = NULL;
	/**
	 * Test poll() and select()
	 */
	public function testPollAndSelect()
	{
		$this->_poll_data = NULL;

		$url = 'http://www.example.com/';

		$c = new Curl_Multi();

		$callback = create_function('$info,$data,$udata', '$udata->_poll_data = $data; $udata->assertEquals("200", $info["http_code"]);');

		$c->addHandle(curl_init($url), $callback, $this);

		// just adding the handle shouldn't trigger the callback
		$this->assertNull($this->_poll_data);

		// calling poll the first time shouldn't trigger the callback (non-blocking request)
		$this->assertTrue($c->poll());
		$this->assertNull($this->_poll_data);

		// this should trigger the callback, since the select() will wait for the request to occur,
		// this select should wait for the request to come back, and since there is only once request,
		// this should return FALSE indicating that there is nothing more to fetch.
		do
		{
			// neccessary for slow returns, we might have to call select a few times before
			// it returns false, but in the end the last call should return FALSE
			$result = $c->select();
		}
		while ($result === TRUE);
		$this->assertFalse($c->select());
		$this->assertContains("Example Web Page", $this->_poll_data);
	}

	/**
	 * data for this test
	 * @var integer $_callbacks
	 */
	public $_callbacks = 0;
	/**
	 * tests finish() func
	 */
	public function testFinish()
	{
		$url = 'http://www.example.com/';

		$c = new Curl_Multi();

		$callback = create_function('$info,$data,$udata', '$udata->_callbacks++; $udata->assertEquals("200", $info["http_code"]);;');

		// twice as fun!
		$h1 = curl_init($url);
		$h2 = curl_init($url);

		$c->addHandle($h1, $callback, $this);
		$c->addHandle($h2, $callback, $this);

		// finish always returns true.
		$this->assertTrue($c->finish());

		// make sure we got two requests back.
		$this->assertEquals(2, $this->_callbacks);

		// running it again isn't harmful...
		$this->assertTrue($c->finish());
		$this->assertEquals(2, $this->_callbacks);
	}

	/**
	 * tests __destruct() function
	 *
     * @expectedException PHPUnit_Framework_Error
     */
	public function testDestruct()
	{
		$c = new Curl_Multi();

		$h = curl_init('http://www.example.com/');

		$c->addHandle($h, create_function('',''), NULL);

 		// triggers __destruct().
 		// should remove and close all handles.
		$c = NULL;

		// should fail, since handle is closed.
		$this->assertNull(curl_exec($h));
	}
}
