<?php
/**
 * Unstable testing code.
 */
 
require_once 'PHPUnit/Framework.php';
require_once '../src/QueryPath/CssEventHandler.php';
require_once '../src/QueryPath/BottomUpCssEventHandler.php';
require_once 'CssEventTests.php';

/**
 * Tests for QueryPathCssEventHandler class.
 * @ingroup querypath_tests
 */
class BottomUpCssEventHandlerTests extends QueryPathCssEventHandlerTests {
  public $xml = '<?xml version="1.0" ?>
  <html>
  <head>
    <title>This is the title</title>
  </head>
  <body>
    <div id="one">
      <div id="two" class="class-one">
        <div id="three">
        Inner text.
        </div>
      </div>
    </div>
    <span class="class-two">Nada</span>
    <p><p><p><p><p><p><p class="Odd"><p>8</p></p></p></p></p></p></p></p>
    <ul>
      <li class="Odd" id="li-one">Odd</li>
      <li class="even" id="li-two">Even</li>
      <li class="Odd" id="li-three">Odd</li>
      <li class="even" id="li-four">Even</li>
      <li class="Odd" id="li-five">Odd</li>
      <li class="even" id="li-six">Even</li>
      <li class="Odd" id="li-seven">Odd</li>
      <li class="even" id="li-eight">Even</li>
      <li class="Odd" id="li-nine">Odd</li>
      <li class="even" id="li-ten">Even</li>
    </ul>
  </body>
  </html>
  ';
  
  
  public function testGetMatches() {
    // Test root element:
    $xml = '<?xml version="1.0" ?><test><inside/>Text<inside/></test>';
    $doc = new DomDocument();
    $doc->loadXML($xml);
    
    // Test handing it a DOM Document
    $handler = new BottomUpCssEventHandler($doc);
    $matches = $handler->getMatches();
    $this->assertTrue(count($matches) == 1);
    $match = $matches[0];
    $this->assertEquals('test', $match->tagName);

    // Test handling single element
    $root = $doc->documentElement;
    $handler = new BottomUpCssEventHandler($root);
    $matches = $handler->getMatches();
    $this->assertEquals(1, count($matches));
    $match = $matches[0];
    $this->assertEquals('test', $match->tagName);
    
    // Test handling a node list
    $eles = $doc->getElementsByTagName('inside');
    $handler = new BottomUpCssEventHandler($eles);
    $matches = $handler->getMatches();
    $this->assertEquals(2, count($matches));
    $match = $matches[0];
    $this->assertEquals('inside', $match->tagName);
    
    // Test handling an array of elements
    $eles = $doc->getElementsByTagName('inside');
    $array = array();
    foreach ($eles as $ele) $array[] = $ele;
    $handler = new BottomUpCssEventHandler($array);
    $matches = $handler->getMatches();
    $this->assertEquals(2, count($matches));
    $match = $matches[0];
    $this->assertEquals('inside', $match->tagName);
  }
  
  public function testElement() {
    $xml = '<?xml version="1.0" ?><test><inside id="first"/>Text<inside/></test>';
    $doc = new DomDocument();
    $doc->loadXML($xml);

    // Basic test
    $handler = new BottomUpCssEventHandler($doc);
    $handler->find('inside');
    $matches = $handler->getMatches();
    $this->assertEquals(2, count($matches));
    $match = $matches[0];
    $this->assertEquals('inside', $match->tagName);
    
    $doc = new DomDocument();
    $doc->loadXML($this->xml);

    // Test getting nested
    $handler = new BottomUpCssEventHandler($doc);
    $handler->find('div');
    $matches = $handler->getMatches();
    $this->assertEquals(3, count($matches));
    $match = $matches[0];
    $this->assertEquals('div', $match->tagName);
    $this->assertEquals('one', $match->getAttribute('id'));
    
    // Test getting a list
    $handler = new BottomUpCssEventHandler($doc);
    $handler->find('li');
    $matches = $handler->getMatches();
    $this->assertEquals(10, count($matches));
    $match = $matches[0];
    //$this->assertEquals('div', $match->tagName);
    $this->assertEquals('li-one', $match->getAttribute('id'));
    
    // Test getting the root element
    $handler = new BottomUpCssEventHandler($doc);
    $handler->find('html');
    $matches = $handler->getMatches();
    $this->assertEquals(1, count($matches));
    $match = $matches[0];
    $this->assertEquals('html', $match->tagName);
  }
  
  public function testElementId() {
    // Test root element:
    $xml = '<?xml version="1.0" ?><test><inside id="first"/>Text<inside/></test>';
    $doc = new DomDocument();
    $doc->loadXML($xml);
    
    $handler = new BottomUpCssEventHandler($doc);
    $handler->find('#first');
    $matches = $handler->getMatches();
    $this->assertEquals(1, count($matches));
    $match = $matches[0];
    $this->assertEquals('inside', $match->tagName);
    
    // Test a search with restricted scope:
    $handler = new BottomUpCssEventHandler($doc);
    $handler->find('inside#first');
    $matches = $handler->getMatches();
    $this->assertEquals(1, count($matches));
    $match = $matches[0];
    $this->assertEquals('inside', $match->tagName);
    
  }
  
}